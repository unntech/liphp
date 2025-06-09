<?php
declare (strict_types = 1);

use LiPhp\Config;
use LiPhp\Session;
use App\framework\LiApp;

if (!function_exists('dv')) {
    /**
     * 浏览器友好的变量输出
     *
     * @param  mixed        $var     变量
     * @param  boolean      $echo    是否输出 默认为True 如果为false 则返回输出字符串
     * @param  string|null  $label   标签 默认为空
     * @param  boolean      $strict  是否严谨 默认为true
     */
    function dv(mixed $var, bool $echo = true, string $label = null, bool $strict = true): bool|string|null
    {
        $label = (null === $label) ? '' : rtrim($label) . ' ';
        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            } else {
                $output = $label . print_r($var, true);
            }
        } else {
            ob_start();
            var_dump($var);
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            }
        }
        if ($echo) {
            echo($output);
            return null;
        } else {
            return $output;
        }
    }
}

if (!function_exists('console_log')) {
    /**
     * 通过JS把PHP变量输出至 console.log
     * @param $output
     * @param bool $with_script_tags
     * @return void
     */
    function console_log($output, bool $with_script_tags = true): void
    {
        $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) .
            ');';
        if ($with_script_tags) {
            $js_code = '<script>' . $js_code . '</script>';
        }
        echo $js_code;
    }
}

if (!function_exists('config')) {
    function config(string $name = null, $default = null){
        if (str_contains($name, '.')) {
            $v = explode('.',$name);
            $key = $v[0];
        }else{
            $key = $name;
        }
        if(!Config::exists($key)){
            Config::load($key);
        }
        return Config::get($name, $default);
    }
}

if (!function_exists('session')) {
    function session($name = '', $value = '')
    {
        if(empty(Session::$session_id)){
            $opt = config('session');
            if($opt['save'] == 'redis'){
                if(empty(LiApp::$redis)){
                    LiApp::set_redis();
                }
                $opt['handle'] = LiApp::$redis;
            }
            Session::start($opt);
        }

        if (is_null($name)) {
            // 清除
            Session::clear();
        } elseif ('' === $name) {
            return Session::get();
        } elseif (is_null($value)) {
            // 删除
            Session::delete($name);
        } elseif ('' === $value) {
            // 获取
            return Session::get($name);
        } else {
            // 设置
            Session::set($name, $value);
        }
    }
}

if (!function_exists('exception_handler')) {
    /**
     * 全局通用异常处理过程
     * @param Throwable $e
     * @return void
     */
    function exception_handler(Throwable $e): void
    {
        if($_SERVER['REQUEST_METHOD'] === 'POST'){ // isAjax Json Request
            if (defined('DT_DEBUG') && DT_DEBUG) {
                $postDate = json_decode(file_get_contents("php://input"), true);
                $data = ['request'=>$postDate, 'exception'=>$e, 'code'=>$e->getCode(),'message'=>$e->getMessage(), 'trace'=>$e->getTrace()];
            }else{
                $data = ['code'=>$e->getCode(),'message'=>$e->getMessage()];
            }
            echo json_encode($data);
        }else {
            if (defined('DT_DEBUG') && DT_DEBUG) {
                $now = microtime(true);
                $start = defined('APP_START_TIME') ? APP_START_TIME : $_SERVER['REQUEST_TIME_FLOAT'];
                $runtime = round(($now - $start) * 1000, 3);
                $html = '<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"><title>HTTP 500</title><style>body{margin: 0 auto;} .header{background: #6c757d; color: #eee; padding: 50px 15px 30px 15px;line-height: 1.5rem} .msg{padding: 15px 15px;line-height: 1.25rem}</style></head><body>';
                $html .= '<div class="header"><h3>' . $e->getMessage() . '</h3>Code: ' . $e->getCode() . '<BR>File: ' . $e->getFile() . '<BR>Line: ' . $e->getLine() . '<BR>Elapsed: '.$runtime.' ms</div>';
                $html .= '<div class="msg">' . dv($e, false) . '</div>';
                $html .= '</body></html>';
            } else {
                $msg = $e->getCode() . ': ' . $e->getMessage();
                $html = '<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"><title>HTTP 500</title><style>body{background-color:#444;font-size:16px;}h3{font-size:32px;color:#eee;text-align:center;padding-top:50px;font-weight:normal;}</style></head>';
                $html .= '<body><h3>' . $msg . '</h3></body></html>';
            }
            echo $html;
        }
    }
}
