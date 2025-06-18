<?php
declare (strict_types = 1);

namespace LiPhp;

/**
 * @method static Mysqli|SqlSrv|MongoDB table(string $table, ?string $alias= null)
 * @method static Mysqli|SqlSrv|MongoDB where(string|array $condition)
 * @method static Mysqli|SqlSrv fields(string|array $fields)
 * @method static string getLastSql()
 * @method static array getOptions()
 * @method static Mysqli|SqlSrv query(string $sql)
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
     * @var Mysqli | SqlSrv | MongoDB
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
                $db = new Mysqli($cfg);
                break;
            case 'sqlsrv':
                $db = new SqlSrv($cfg);
                break;
            case 'mongodb':
				$db = new MongoDB($cfg);
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