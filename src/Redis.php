<?php
declare (strict_types = 1);

namespace LiPhp;

class Redis {
    public static $pre;
    /**
     * @var \redis()
     */
    public static $redis;
    
    /**
     * 构造方法
     * @access public
     */
    public static function Create($rediscfg)
    {
        $cfg = $rediscfg['connections'];
        self::$pre = $cfg['prefix'];
        $redis = new \redis();
        $redis->connect( $cfg['host'], $cfg['port'] );
        if(!empty($cfg['password'])){
            $redis->auth($cfg['password']);
        }
        if(!empty($cfg['db'])){
            $redis->select($cfg['db']); 
        }
        self::$redis = $redis;
        return $redis;
    }
    
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::$redis, $method], $args);
    }
    
    public static function set(string $key, $value, $ttl=0)
    {
        
        return $ttl > 0 ? self::$redis->set(self::$pre.$key, $value, $ttl) : self::$redis->set(self::$pre.$key, $value);
        
    }
    
    public static function get(string $key)
    {
        
        return self::$redis->get(self::$pre.$key);
        
    }
    
    public static function del(string $key)
    {
        
        return self::$redis->del(self::$pre.$key);
        
    }
    
    public static function incr(string $key)
    {
        
        return self::$redis->incr(self::$pre.$key);
        
    }
    
    public static function decr(string $key)
    {
        
        return self::$redis->decr(self::$pre.$key);
        
    }
    
    public static function exists(string $key)
    {
        
        return self::$redis->exists(self::$pre.$key);
        
    }
    
    public static function expire(string $key, $ttl=0)
    {
        
        return self::$redis->expire(self::$pre.$key, $ttl);
        
    }
    
    public static function georadius(string $key, $longitude, $latitude, $radius, $unit, array $options = null)
    {

        return self::$redis->geoRadius($key, $longitude, $latitude, $radius, $unit, $options);
        
    }

}