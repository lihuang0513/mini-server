<?php

namespace common\components;


use GuzzleHttp\Client;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\FileHelper;
use yii\web\Request;

class Logger extends Component
{


    public static function getLogDir()
    {
        $logPath = Yii::getAlias('@common/runtime/logs/daily/'.date('Y').'/'.date('md'));
        if (!is_dir($logPath)) {
            try {
                FileHelper::createDirectory($logPath, 0777, true);
            } catch (Exception $e) {
                return '';
            }
        }
        return $logPath;
    }

    public static function formatTraceMsg($traceLevel = 3)
    {
        $traces = [];
        if ($traceLevel > 0) {
            $count = 0;
            $ts = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_pop($ts); // remove the last trace since it would be the entry script, not very useful
            foreach ($ts as $trace) {
                if(isset($trace['file'], $trace['line']) && strpos($trace['file'], __FILE__) !== false) {
                    continue;
                }
                if (isset($trace['file'], $trace['line']) && strpos($trace['file'], YII2_PATH) !== 0) {
                    unset($trace['object'], $trace['args']);
                    $traces[] = $trace;
                    if (++$count >= $traceLevel) {
                        break;
                    }
                }
            }
        }

        $trace_msg = [];
        if ($traces) {
            foreach ($traces as $trace) {
                $trace_msg[] = "in {$trace['file']}:{$trace['line']}";
            }
        }

        return $trace_msg;
    }

    public function write($message, $category = 'application')
    {
        if(!$category) {
            $category = 'application';
        }
        Yii::info($message, 'logger.'.$category);
        $logFile = Yii::getAlias('@common/runtime/logs/daily/'.date('Y').'/'.date('md').'/'.$category.'.log');
        $isNewFile = file_exists($logFile)?false:true;
        $logPath = dirname($logFile);
        if (!is_dir($logPath)) {
            try {
                FileHelper::createDirectory($logPath, 0777, true);
            } catch (Exception $e) {
            }
        }
        $text = $this->getMessagePrefix().' '.$this->prepareMessage($message);
        @file_put_contents($logFile, $text."\n", FILE_APPEND | LOCK_EX);
        if($isNewFile) {
            @chmod($logFile, 0666);
        }
    }


    public function debug($message, $category = 'application')
    {
        if(!YII_DEBUG) {
            return false;
        }
        if(!$category) {
            $category = 'application';
        }
        Yii::info($message, 'logger.'.$category);
        $logFile = Yii::getAlias('@common/runtime/logs/daily/'.date('Y').'/'.date('md').'/'.$category.'.log');
        $isNewFile = file_exists($logFile)?false:true;
        $logPath = dirname($logFile);
        if (!is_dir($logPath)) {
            try {
                FileHelper::createDirectory($logPath, 0777, true);
            } catch (Exception $e) {
            }
        }
        $text = $this->getMessagePrefix().' '.$this->prepareMessage($message);
        @file_put_contents($logFile, $text."\n", FILE_APPEND | LOCK_EX);
        if($isNewFile) {
            @chmod($logFile, 0666);
        }
    }

    /**
     * @param $notice
     * @param $category
     * @param $type
     * @throws \Exception
     */
    private function sendSysAdminNotice($notice, $category, $type = '出错')
    {
//        /**
//         * OPENTM207123406
//         * {{first.DATA}}
//        待办名称：{{keyword1.DATA}}
//        状态：{{keyword2.DATA}}
//        时间：{{keyword3.DATA}}
//        备注：{{keyword4.DATA}}
//        {{remark.DATA}}
//         */
//        $open_id_array = $this->getSysAdminOpenId();
//        foreach($open_id_array as $open_id) {
//            $data = [
//                'first'=>'新小店 on '.YII_ENV,
//                'keyword1'=>$type,
//                'keyword2'=>$category,
//                'keyword3'=>date('Y-m-d H:i:s'),
//                'keyword4'=>'详细信息请查看',
//                'remark'=>$notice,
//            ];
//            //WxComponentBizTemplate::sendMpTemplate('wx32dc16a063b5d539', 'OPENTM207123406', $open_id, $data);
//            WxComponentBizTemplate::pushMpQueue('wx32dc16a063b5d539', $open_id, 'OPENTM207123406', $data);
//        }

        $logToken = self::getRequestToken();

        $ip = (gethostbyname(gethostname()));
        $content = "{$type} ".YII_ENV.":".$ip."\n";
        $content .= "LOG_TOKEN：{$logToken}\n";
        $content .= "日志：{$category}\n";
        $content .= "时间：".(date('Y-m-d H:i:s'))."\n";
        $content .= "信息：".$notice;
        $this->sendServerHook($content);
    }

    private function sendServerHook($content)
    {
        $msg = [
            "msgtype" => "text",
            'text' => ['content'=>$content],
        ];

        $client = new Client();
        $res = $client->request('POST', 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=0b54b27a-698d-4961-b0ca-84aab9e857e2', [
            'json'=>$msg
        ]);

        //print_r($res->getBody()->getContents());
    }

    private function getSysAdminOpenId()
    {
//        //返回新小店公众号的管理员open_id
//        return [
//            'oHTstwzACQ9QnakKh6D0qVTPM1AE', //david
//            'oHTstwxg2f1rQFjighZeruVGVgKc', //waiting
//            'oHTstw0Q8kdQJ0gR9xOtBcdwU6bg', //wuyq
//        ];
        
        $openids = \Yii::$app->params['adminopenid'];
        return $openids;
    }

    /**
     * @param $message
     * @param string $category
     */
    public function error($message, $category = 'application')
    {
        if(!$category) {
            $category = 'application';
        }
        Yii::info($message, 'logger.'.$category);
        $logFile = Yii::getAlias('@common/runtime/logs/daily/'.date('Y').'/'.date('md').'/'.$category.'.log');
        $isNewFile = file_exists($logFile)?false:true;
        $logPath = dirname($logFile);
        if (!is_dir($logPath)) {
            try {
                FileHelper::createDirectory($logPath, 0777, true);
            } catch (Exception $e) {
            }
        }

        $notice = $this->prepareMessage($message);
        $text = $this->getMessagePrefix().' '.$notice;
        @file_put_contents($logFile, $text."\n", FILE_APPEND | LOCK_EX);

        try{
            $this->sendSysAdminNotice($notice, $category);
        }catch (\Exception $e) {
            $text = $this->getMessagePrefix().' 发送管理员通知失败:'.$e->getMessage();
            @file_put_contents($logFile, $text."\n", FILE_APPEND | LOCK_EX);
            if($isNewFile) {
                @chmod($logFile, 0666);
            }
        }

    }


    private function formatTrace($debugTraces)
    {
        $traces = [];

        $ts = $debugTraces;
        array_pop($ts); // remove the last trace since it would be the entry script, not very useful
        foreach ($ts as $trace) {
            if(isset($trace['file'], $trace['line']) && strpos($trace['file'], __FILE__) !== false) {
                continue;
            }
            if (isset($trace['file'], $trace['line']) && strpos($trace['file'], YII2_PATH) !== 0) {
                unset($trace['object'], $trace['args']);
                $traces[] = $trace;
            }
        }

        $tracesLines = [];
        foreach ($traces as $trace) {
            $tracesLines[] = "in {$trace['file']}:{$trace['line']}";
        }

        return trim(implode("\n    ", $tracesLines));
    }

    /**
     * @param $message
     * @param string $category
     */
    public function notice($message, $category = 'application')
    {
        if(!$category) {
            $category = 'application';
        }
        Yii::info($message, 'logger.'.$category);
        $logFile = Yii::getAlias('@common/runtime/logs/daily/'.date('Y').'/'.date('md').'/'.$category.'.log');
        $isNewFile = file_exists($logFile)?false:true;
        $logPath = dirname($logFile);
        if (!is_dir($logPath)) {
            try {
                FileHelper::createDirectory($logPath, 0777, true);
            } catch (Exception $e) {
            }
        }

        $notice = $this->prepareMessage($message);
        $text = $this->getMessagePrefix().' '.$notice;
        @file_put_contents($logFile, $text."\n", FILE_APPEND | LOCK_EX);

        try{
            $this->sendSysAdminNotice($message, $category, '提示');
        }catch (\Exception $e) {
            $text = $this->getMessagePrefix().' 发送管理员通知失败:'.$e->getMessage();
            @file_put_contents($logFile, $text."\n", FILE_APPEND | LOCK_EX);

            @chmod($logFile, 0666);
        }

    }

    private static function getCalledLine($level = 1)
    {
        $level ++;
        $log = debug_backtrace();
        if($log[$level]) {
            $item = $log[$level];
        } elseif($level>1) {
            if(isset($log[$level-1])) {
                $item = $log[$level-1];
            } else {
                $item = [];
            }
        } else {
            $item = [];
        }

        return $item;
    }

    public function prepareMessage($string, $track_level = 1)
    {
        if(is_object($string) && is_a($string, \Throwable::class)) {
            /**
             * @var $string \Throwable
             */
            $message = $string->getMessage();
            $line = $string->getLine();
            $file = $string->getFile();
            $trace = $this->formatTrace($string->getTrace());
            $string = "[{$file}:{$line}] {$message}\n{$trace}";
        }elseif(!is_string($string)) {
            if(is_object($string) && is_callable([$string, '__toString'])) {
                $string = $string->__toString();
                $trace = self::getCalledLine($track_level);
                if($trace) {
                    $string = sprintf("[%s:%d] %s", $trace['file'], $trace['line'], $string);
                } else {
                    $string = sprintf("%s",  $string);
                }
            } else {
                $trace = self::getCalledLine($track_level);
                if($trace) {
                    $string = sprintf("[%s:%d] %s", $trace['file'], $trace['line'], var_export($string, TRUE));
                } else {
                    $string = sprintf("%s",  var_export($string, TRUE));
                }
            }

            //$string = var_export($string, true);
        } else {
            $trace = self::getCalledLine($track_level);
            if($trace) {
                $string = sprintf("[%s:%d] | %s", $trace['file'], $trace['line'], $string);
            } else {
                $string = sprintf("%s",  $string);
            }
        }

        return $string;
    }

    private static $requestToken = '';


    public static function getRequestToken()
    {
        if(!self::$requestToken) {
            self::$requestToken = 'TK'.strtoupper(md5(uniqid()));
        }
        return self::$requestToken;
    }


    public static function regenerateToken()
    {
        self::$requestToken = 'TK'.strtoupper(md5(uniqid()));
        return self::$requestToken;
    }


    /**
     * Returns a string to be prefixed to the given message.
     * If [[prefix]] is configured it will return the result of the callback.
     * The default implementation will return user IP, user ID and session ID as a prefix.
     * The message structure follows that in [[Logger::messages]].
     * @return string the prefix string
     */
    public function getMessagePrefix()
    {

        if (Yii::$app === null) {
            return '';
        }

        $request = Yii::$app->getRequest();
        $ip = $request instanceof Request ? $request->getUserIP() : '-';

        /* @var $user \yii\web\User */
        $user = Yii::$app->has('user', true) ? Yii::$app->get('user') : null;
        if ($user && ($identity = $user->getIdentity(false))) {
            $userID = $identity->getId();
        } else {
            $userID = '-';
        }

        /* @var $session \yii\web\Session */
        $session = Yii::$app->has('session', true) ? Yii::$app->get('session') : null;
        $sessionID = $session && $session->getIsActive() ? $session->getId() : '-';

        $pid = posix_getpid();
        if(!$pid) {
            $pid = '-';
        }

        $time = date('Y-m-d H:i:s');

        $requestToken = self::getRequestToken();

        $appId = Yii::$app->id;

        return "[$time][$pid][$ip][$userID][$sessionID][$requestToken][$appId]";
    }
}