<?php
declare (strict_types = 1);

namespace LiPhp;

/**
 * @method static mysqli|sqlsrv|mongodb table(string $table, ?string $alias= null)
 * @method static mysqli|sqlsrv|mongodb where(string|array $condition)
 * @method static mysqli|sqlsrv fields(string|array $fields)
 * @method static string getLastSql()
 * @method static array getOptions()
 * @method static mysqli|sqlsrv query(string $sql)
 * @method static \mysqli_result|resource startTrans()
 * @method static \mysqli_result|resource commit()
 * @method static \mysqli_result|resource rollback()
 * @method static bool tableExists(string $tableName)
 * @method static int errno()
 * @method static string error()
 */

abstract class Db
{
    /**
     * @var mysqli | sqlsrv | mongodb
     */
    public static $db;
    
    /**
     * 构造方法
     * @access public $i 为配置文件db列表里的第几个配置
     */
    public static function Create($icfg, $i=0, $new = false)
    {
        $cfg = $icfg['connections'][$i];
        $dbt = $cfg['database'];
        switch($dbt){
            case 'mysqli':
                $db = new mysqli($cfg);
                break;
            case 'sqlsrv':
                $db = new sqlsrv($cfg);
                break;
            case 'mongodb':
				$db = new mongodb($cfg);
				break;
            default :
                $db = false;
        }
        if(!$new) self::$db = $db;
        return $db;
    }
    
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::$db, $method], $args);
    }

}