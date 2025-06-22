<?php
declare (strict_types = 1);

namespace LiPhp;

use LiPhp\Models\Result;

/**
 * @method static Mysqli|SqlSrv|MongoDB|PgSql table(string $table, ?string $alias= null)
 * @method static Mysqli|SqlSrv|MongoDB|PgSql where(string|array $condition)
 * @method static Mysqli|SqlSrv|PgSql fields(string|array $fields)
 * @method static string getLastSql()
 * @method static array getOptions()
 * @method static Mysqli|SqlSrv|PgSql query(string $sql)
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
     * @var Mysqli | SqlSrv | MongoDB | PgSql
     */
    public static $db;
    
    /**
     * 构造方法
     * @access public $i 为配置文件db列表里的第几个配置
     */
    public static function Create(array $config, int $i=0, bool $new = false)
    {
        $cfg = $config['connections'][$i];
        $dbt = strtolower($cfg['database']);
        $db = match ($dbt) {
            'mysqli'    => new Mysqli($cfg),
            'sqlsrv'    => new SqlSrv($cfg),
            'mongodb'   => new MongoDB($cfg),
            'pgsql'     => new PgSql($cfg),
            default     => false,
        };
        if(!$new) self::$db = $db;
        return $db;
    }
    
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::$db, $method], $args);
    }

}