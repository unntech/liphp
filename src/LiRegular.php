<?php
declare (strict_types = 1);

namespace LiPhp;

class LiRegular {

    /**
     * 正则模式-整数
     * @var string
     */
    public static string $patternInteger = "/^((:?+|-)?[0-9]+)$/";


    /**
     * 正则模式-自然数
     * @var string
     */
    public static string $patternNaturalNum = '/^(?:0|[1-9][0-9]*)$/';


    /**
     * 正则模式-字母
     * @var string
     */
    public static string $patternAlpha = '/^[A-Za-z]+$/';


    /**
     * 正则模式-字母或数字
     * @var string
     */
    public static string $patternAlphaNum = '/^[A-Za-z0-9]+$/';


    /**
     * 正则模式-字母或数字或下划线
     * @var string
     */
    public static string $patternAlphaNumDash = '/^[A-Za-z0-9\_]+$/';


    /**
     * 正则模式-字母或中文
     * @var string
     */
    public static string $patternAlphaChinese = '/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u';


    /**
     * 正则模式-字母或数字或中文
     * @var string
     */
    public static string $patternAlphaNumChinese = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u';


    /**
     * 正则模式-字母或数字或下划线或中文
     * @var string
     */
    public static string $patternAlphaNumDashChinese = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_]+$/u';


    /**
     * 正则模式-二进制
     * @var string
     */
    public static string $patternBinary = "/[^\x20-\x7E\t\r\n]/";


    /**
     * 正则模式-十六进制
     * @var string
     */
    public static string $patternHex = "/^0x[0-9a-f]+$/i";


    /**
     * 正则模式-邮箱
     * @var string
     */
    public static string $patternEmail = "/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/";


    /**
     * 正则模式-中国大陆手机号
     * @var string
     */
    public static string $patternMobilecn = "/^1[3456789]\d{9}$/";


    /**
     * 正则模式-座机号
     * @var string
     */
    public static string $patternTel = "/^(010|02\d{1}|0[3-9]\d{2})-\d{7,9}(-\d+)?$/";


    /**
     * 正则模式-座机号400/800
     * @var string
     */
    public static string $patternTel4800 = "/^[48]00\d?(-?\d{3,4}){2}$/";


    /**
     * 正则模式-URL
     * @var string
     */
    public static string $patternUrl = '/^http[s]?:\/\/' . '(([0-9]{1,3}\.){3}[0-9]{1,3}' . // IP形式的URL- 199.194.52.184
    '|' . // 允许IP和DOMAIN（域名）
    '([0-9a-z_!~*\'()-]+\.)*' . // 三级域验证- www.
    '([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\.' . // 二级域验证
    '[a-z]{2,6})' .  // 顶级域验证.com or .museum
    '(:[0-9]{1,4})?' .  // 端口- :80
    '((\/\?)|' .  // 如果含有文件对文件部分进行校验
    '(\/[0-9a-zA-Z_!~\*\'\(\)\.;\?:@&=\+\$,%#-\/]*)?)$/';


    /**
     * 正则模式-中国大陆身份证号码
     * @var string
     */
    public static string $patternCnIdNo = "/^([\d]{17}[xX\d]|[\d]{15})$/";


    /**
     * 正则模式-QQ号码
     * @var string
     */
    public static string $patternQQNo = '/^[1-9][0-9]{5,16}$/';


    /**
     * 正则模式-中文字符
     * @var string
     */
    public static string $patternChineseChar = "/[\x{4e00}-\x{9fa5}]+/u";


    /**
     * 正则模式-全中文
     * @var string
     */
    public static string $patternAllChinese = "/^[\\x{4e00}-\\x{9fa5}]+$/u";


    /**
     * 正则模式-含中文
     * @var string
     */
    public static string $patternHasChinese = "/[\\x{4e00}-\\x{9fa5}]/u";


    /**
     * 正则模式-宽字节(双字节)字符
     * @var string
     */
    public static string $patternWidthChar = "/[^\\x{00}-\\x{ff}]/u";


    /**
     * 正则模式-全英文-小写
     * @var string
     */
    public static string $patternAllLetterLower = "/^[a-z]+$/";


    /**
     * 正则模式-全英文-大写
     * @var string
     */
    public static string $patternAllLetterUpper = "/^[A-Z]+$/";


    /**
     * 正则模式-全英文-忽略大小写
     * @var string
     */
    public static string $patternAllLetter = "/^[a-z]+$/i";


    /**
     * 正则模式-含英文
     * @var string
     */
    public static string $patternHasLetter = "/[a-z]+/i";


    /**
     * 正则模式-词语,不以下划线开头的中文、英文、数字、下划线
     * @var string
     */
    public static string $patternWord = "/^(?!_)[\\x{4e00}-\\x{9fa5}a-zA-Z0-9_]+$/u";


    /**
     * 正则模式-日期时间
     * @var string
     */
    public static string $patternDatetime = "/^[0-9]{4}(|\-[0-9]{2}(|\-[0-9]{2}(|\s+[0-9]{2}(|:[0-9]{2}(|:[0-9]{2})))))$/";


    /**
     * 正则模式-base64编码图片
     * @var string
     */
    public static string $patternBase64Image = "/^data:\s*(image|img)\/(\w+);base64,/i";


    /**
     * 正则模式-IPv4
     * @var string
     */
    public static string $patternIPv4 = "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/";


    /**
     * 正则模式-连续的"//"或"\\"或"\/"或"/\"
     * @var string
     */
    public static string $patternDoubleSlash = '/[\/\\\\]{2,}/';


    /**
     * 正则模式-全空格(包括半角/全角空格和TAB)
     * @var string
     */
    public static string $patternSpace = '/^[(\xc2\xa0)|[:blank:]]+$/u';


    /**
     * 正则模式-全空白符
     * @var string
     */
    public static string $patternWhitespace = '/^[(\xc2\xa0)|\s]+$/u';


    /**
     * 正则模式-多字节字符
     * @var string
     */
    public static string $patternMultibyte = '/[^\x00-\x7F]/u';


    /**
     * 正则模式-连续的空白符
     * @var string
     */
    public static string $patternWhitespaceDuplicate = '/[(\xc2\xa0)|\s]{2,}/u';


    /**
     * 正则模式-物理地址
     * @var string
     */
    public static string $patternMacAddress = '/([0-9A-F]{2}[:-]){5}([0-9A-F]{2})/i';


    /**
     * 正则模式-括号及括号内容
     * @var array
     */
    public static array $patternBrackets = [
        '1'  => '/\(([^\(]*?)\)/is',
        '2'  => '/\[([^\[]*?)\]/is',
        '4'  => '/\{([^\{]*?)\}/is',
        '8'  => '/\<([^\<]*?)\>/is',
        '16' => '/（([^（]*?)）/u',
        '32' => '/【([^【]*?)】/u',
        '64' => '/《([^《]*?)》/u',
    ];


}