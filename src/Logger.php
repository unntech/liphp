<?php
declare (strict_types = 1);

namespace LiPhp;

use MongoDB\BSON\UTCDateTime;

class Logger
{
    // 日志级别常量
    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_NOTICE = 2;
    const LEVEL_WARNING = 3;
    const LEVEL_ERROR = 4;
    const LEVEL_CRITICAL = 5;
    const LEVEL_NONE = 6;

    const LEVEL_NAME = ['DEBUG',  'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'NONE'];
    protected int $logLevel = self::LEVEL_WARNING;
    protected array $outputTargets = [];
    protected string $dateFormat = 'Y-m-d H:i:s';
    protected static $instance;

    /**
     * 构造函数
     */
    public function __construct()
    {

    }

    public static function instance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;

    }

    /**
     * 设置日志级别
     */
    public function setLogLevel(int $level): void
    {
        $this->logLevel = $level;
    }

    /**
     * 添加输出目标
     * @param callable $target 输出目标回调函数
     * @param array $param 默认值字符串 ['target'=>'MongoDB' | 'array' | 'custom'] <br>
     *        target = custom 时，则需要调用 custom()方法写入
     * @return void
     */
    public function addOutputTarget(callable $target, array $param = []): void
    {
        $param['target'] = $param['target'] ? strtolower($param['target']) : 'string';
        $this->outputTargets[] = ['callable'=>$target, 'param'=>$param];
    }

    /**
     * 日记写到文件
     * @param string $filename
     * @return void
     */
    public function addOutputTargetFile(string $filename): void
    {
        if(!str_starts_with($filename, '/')){
            $filename = Lite::getRootPath().'/runtime/log/'.$filename;
        }
        $this->createDirectory(dirname($filename));
        $this->addOutputTarget(function ($message) use ($filename) {
            file_put_contents($filename, $message, FILE_APPEND);
        });
    }

    /**
     * 日志直接输出
     * @return void
     */
    public function addOutputTargetEcho(): void
    {
        // 添加控制台输出（调试时使用）
        $this->addOutputTarget(function ($message) {
            echo $message;
        });
    }

    /**
     * 日记写到 MongoDB
     * @param MongoDB $db
     * @param string $collection
     * @return void
     */
    public function addOutputTargetMongoDB(MongoDB $db, string $collection): void
    {
        $this->addOutputTarget(function ($data) use ($db, $collection) {
            $db->table($collection)->insert($data);
        }, ['target'=>'MongoDB']);
    }

    /**
     * 日志写到 Mysqli | SqlSrv
     * @param $db
     * @param string $table
     * @return void
     */
    public function addOutputTargetDB($db, string $table): void
    {
        $this->addOutputTarget(function ($data) use ($db, $table) {
            $db->table($table)->insert($data);
        }, ['target'=>'array']);
    }

    public function addOutputTargetCustomDB($db, string $table): void
    {
        $this->addOutputTarget(function ($data) use ($db, $table) {
            $db->table($table)->insert($data);
        }, ['target'=>'custom']);
    }

    /**
     * 设置日期格式
     */
    public function setDateFormat(string $format): void
    {
        $this->dateFormat = $format;
    }

    /**
     * 记录日志
     */
    public function log(int $level, string $message, array|object|string $context = []): void
    {
        // 检查日志级别
        if (!$this->isLevelEnabled($level)) {
            return;
        }

        // 输出到所有目标
        foreach ($this->outputTargets as $target) {
            $_target = $target['param']['target'];
            if($_target != 'custom'){
                if(!isset($logMessage[$_target])){
                    // 格式化日志信息
                    $logMessage[$_target] = $this->formatMessage(self::LEVEL_NAME[$level], $message, $context, $target['param']);
                }
                $callable = $target['callable'];
                $callable($logMessage[$_target]);
            }
        }
    }

    /**
     * 自定义格式写入日志
     * @param mixed $data
     * @param int $level
     * @return void
     */
    public function custom(mixed $data, int $level = 6): void
    {
        // 检查日志级别
        if (!$this->isLevelEnabled($level)) {
            return;
        }
        // 输出到所有目标
        foreach ($this->outputTargets as $target) {
            if($target['param']['target'] == 'custom'){
                $callable = $target['callable'];
                $callable($data);
            }
        }
    }

    /**
     * 检查日志级别是否启用
     */
    protected function isLevelEnabled(int $level): bool
    {
        return $level >= $this->logLevel;
    }

    /**
     * 格式化日志消息
     */
    protected function formatMessage(string $level, string $message, array|object|string $context, array $param =[]): array|string
    {
        switch ($param['target']){
            case 'mongodb':
                $ret = [
                    'time'    => new UTCDateTime (),
                    'level'   => $level,
                    'message' => $message,
                    'context' => $context,
                ];
                break;
            case 'array':
                $ret = [
                    'time'    => date($this->dateFormat),
                    'level'   => $level,
                    'message' => $message,
                    'context' => is_string($context) ? $context : json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ];
                break;
            default:
                $timestamp = date($this->dateFormat);
                if(empty($context)){
                    $contextMessage = '';
                }else{
                    $contextMessage = ' - ';
                    $contextMessage .= is_string($context) ? $context : json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
                $ret = "[{$timestamp}] [{$level}] {$message}{$contextMessage}" . PHP_EOL;
        }

        return $ret;
    }

    // 便捷方法
    public function debug(string $message, array|object|string $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    public function info(string $message, array|object|string $context = []): void
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    public function notice(string $message, array|object|string $context = []): void
    {
        $this->log(self::LEVEL_NOTICE, $message, $context);
    }

    public function warning(string $message, array|object|string $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    public function error(string $message, array|object|string $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    public function critical(string $message, array|object|string $context = []): void
    {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    protected function createDirectory($dirPath, $permissions = 0755): bool
    {
        if (!is_dir($dirPath)) {
            return mkdir($dirPath, $permissions, true); // 第三个参数true表示递归创建
        }
        return true;
    }
}