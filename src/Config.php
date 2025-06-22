<?php
declare (strict_types = 1);

namespace LiPhp;

/**
 * 配置管理类
 */
class Config
{
    /**
     * 配置参数
     * @var array
     */
    protected static array $config = [];

    /**
     * 配置文件目录
     * @var string
     */
    protected static string $path;

    /**
     * 构造方法
     * @access public
     */
    public static function initialize(string $path = ''): void
    {
        if(empty($path)){
            self::$path = dirname(__DIR__ , 4) . '/config/';
        }else{
            self::$path = $path;
        }
    }
    
    /**
     * 加载配置文件
     * @access public
     * @param array|string $file 配置文件名
     * @return array
     */
    public static function load(array|string $file): array
    {
        if(is_array($file)){
            foreach($file as $k=>$v){
                $fn = self::$path.$v.'.php';
                if(file_exists($fn)){
                    $config = include $fn;
                    self::set($config, $v);
                }else{
                    throw new \InvalidArgumentException("{$fn} not found!");
                }
                
            }
        }else{
            $fn = self::$path.$file.'.php';
            if(file_exists($fn)){
                $config = include $fn;
                self::set($config, $file);
            }else{
                throw new \InvalidArgumentException("{$fn} not found!");
            }
        }

        return self::$config;
    }
    
    /**
     * 获取一级配置
     * @access protected
     * @param  string $name 一级配置名
     * @return array
     */
    protected static function pull(string $name): array
    {
        return self::$config[$name] ?? [];
    }
    
    /**
     * 获取配置参数 为空则获取所有配置
     * @access public
     * @param  ?string $name    配置参数名（支持多级配置 .号分割）
     * @param mixed|null $default 默认值
     * @return mixed
     */
    public static function get(?string $name = null, mixed $default = null): mixed
    {
        // 无参数时获取所有
        if (is_null($name)) {
            return self::$config;
        }

        if (!str_contains($name, '.')) {
            return self::pull($name);
        }

        $name    = explode('.', $name);
        $config  = self::$config;

        // 按.拆分成多维数组进行判断
        foreach ($name as $val) {
            if (isset($config[$val])) {
                $config = $config[$val];
            } else {
                return $default;
            }
        }

        return $config;
    }
    
    /**
     * 设置配置参数 name为数组则为批量设置
     * @access public
     * @param  array  $config 配置参数
     * @param  ?string $name 配置名
     * @return array
     */
    public static function set(array $config, ?string $name = null): array
    {
        if (!empty($name)) {
            if (isset(self::$config[$name])) {
                $result = array_merge(self::$config[$name], $config);
            } else {
                $result = $config;
            }

            self::$config[$name] = $result;
        } else {
            $result = self::$config = array_merge(self::$config, $config);
        }

        return $result;
    }

    /**
     * 判断配置参数是否存在
     * @param string $key
     * @return bool
     */
    public static function exists(string $key): bool
    {
        if(isset(self::$config[$key])){
            return true;
        }else{
            return false;
        }
    }
    
}