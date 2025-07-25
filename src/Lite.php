<?php
declare (strict_types = 1);

namespace LiPhp;

class Lite {
    const VERSION = '2.0.5';
    const Framework = 'LiPhp';
    
    /**
     * 应用根目录
     * @var string
     */
    protected static string $rootPath = '';

    /**
     * 框架目录
     * @var string
     */
    protected static string $LiPath = '';
    
    /**
     * 获取应用根目录
     * @return string
     */
    public static function getRootPath(): string
    {
        if(empty(self::$rootPath)){
            self::$rootPath = dirname(__DIR__ , 4);
        }
        
        return self::$rootPath;
    }
    
    /**
     * 设置应用根目录
     * @param string $path 目录
     * @return void
     */
    public static function setRootPath(string $path): void
    {
        self::$rootPath = $path;
    }
    
    /**
     * 获取框架根目录
     * @return string
     */
    public static function getLitePhpPath(): string
    {
        if(empty(self::$LiPath)){
            self::$LiPath = __DIR__ ;
        }
        
        return self::$LiPath;
    }
    
    
}