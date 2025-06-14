<?php
declare (strict_types = 1);

namespace LiPhp;

/**
 * 雪花算法类
 */
class SnowFlake
{
    const EPOCH    = 1672502400000;
    const max12bit = 4095;
    const max41bit = 1099511627775;

    static int $machineId = 0;

    /**
     * 设置机器号
     * @param int $mId
     * @return void
     */
    public static function machineId(int $mId = 0): void
    {
        self::$machineId = $mId;
    }

    /**
     * 获取雪花算法ID
     * @param int $mId 机器号，0为自动，不大于1024
     * @return int
     */
    public static function generateParticle(int $mId = 0): int
    {
        //mId = 0 自动生成机器号
        if($mId == 0 && empty(self::$machineId)) {
            self::$machineId = ip2long(gethostbyname(gethostname())) % 1024;
        }else{
            self::$machineId = $mId;
        }
        if(self::$machineId < 0 || !is_int(self::$machineId) || self::$machineId >= 1024){
            self::$machineId = 0;
        }

        /*
        * Time - 42 bits
        */
        $time = microtime(true) * 1000;

        /*
        * Substract custom epoch from current time
        */
        $time -= self::EPOCH;

        /*
        * Create a base and add time to it
        */
        //$base = decbin(self::max41bit + $time);  //加上固定值63位
        $base = decbin((int)$time);

        /*
        * Configured machine id - 10 bits - up to 1024 machines
        */

        $machineid = str_pad(decbin(self::$machineId), 10, "0", STR_PAD_LEFT);

        /*
        * sequence number - 12 bits - up to 4096 random numbers per machine
        */
        $random = str_pad(decbin(mt_rand(0, self::max12bit)), 12, "0", STR_PAD_LEFT);

        /*
        * Pack
        */
        $base = $base . $machineid . $random;

        /*
        * Return unique time id no
        */
        return bindec($base);
    }

    public static function timeFromParticle($particle): float|int
    {
        /*
        * Return time
        */
        return bindec(substr(decbin($particle), 0, 41)) - self::max41bit + self::EPOCH;
    }
}