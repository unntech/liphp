<?php
declare (strict_types = 1);

namespace LiPhp;

class LiCrypt
{
    //错误代码
    public int $err;
    protected string $cipher;
    protected string $ckey;
    protected string $iv;
    protected string $salt;

    public function __construct(string $key = '', string $iv = '', string $cipher = 'aes-128-cfb')
    {
        $this->cipher = $cipher;
        $this->ckey = $key;
        $this->iv = empty($iv) ? $this->ivstr($key) : $iv;
        $this->salt = substr(md5('UNN.TECH' . $key) . $this->iv, 3, 16);
        $this->err = 0;
    }

    /**
     * 获取有效密码方式算法列表
     * @return array
     */
    public function getCipher(): array
    {
        return openssl_get_cipher_methods();
    }

    /**
     * 设置加密算法
     * @param string $cipher
     * @return void
     */
    public function setCipher(string $cipher = 'aes-256-cfb'): void
    {
        $this->cipher = $cipher;
    }

    /**
     * 重新设置加密密钥
     * @param string $key
     * @param string $iv
     * @return void
     */
    public function setKey(string $key = '', string $iv = ''): void
    {
        $this->ckey = $key;
        $this->iv = empty($iv) ? $this->ivstr($key) : $iv;
    }

    /**
     * 重新设置加密盐值
     * @param string $salt
     * @return void
     */
    public function setSalt(string $salt = ''): void
    {
        $this->salt = $salt;
    }

    private function ivstr($key): string
    {
        $str = md5($key);
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $str = str_pad($str, $ivlen, '=');
        return substr($str, 0, $ivlen);
    }

    /**
     * 加密
     * @param string $plaintext 需加密的字符串
     * @param string $key
     * @param string $iv
     * @return bool|string 加密后字符串
     */
    public function encrypt(string $plaintext, string $key = '', string $iv = ''): bool|string
    {
        $key = $key == '' ? $this->ckey : $key;
        $iv = $iv == '' ? $this->iv : $iv;

        $ciphertext = openssl_encrypt($plaintext, $this->cipher, $key, 1, $iv);
        if($ciphertext === false) return false;
        $ciphertext = $this->base64UrlEncode($ciphertext);
        return $ciphertext;
    }

    /**
     * 解密
     * @param string $ciphertext 密文
     * @param string $key
     * @param string $iv
     * @return bool|string 解密后字符串
     */
    public function decrypt(string $ciphertext, string $key = '', string $iv = ''): bool|string
    {
        $key = $key == '' ? $this->ckey : $key;
        $iv = $iv == '' ? $this->iv : $iv;

        $ciphertext = $this->base64UrlDecode($ciphertext);
        $original_plaintext = openssl_decrypt($ciphertext, $this->cipher, $key, 1, $iv);
        return $original_plaintext;
    }

    /**
     * 加密
     * @param array|string $arr 需加密的字符串或数组
     * @param string $key
     * @param string $iv
     * @return bool|string 加密后字符串
     */
    public function jencrypt(array|string $arr, string $key = '', string $iv = ''): bool|string
    {
        if (is_array($arr)) {
            $rt = $this->encrypt(json_encode($arr), $key, $iv);
        } else {
            $rt = $this->encrypt($arr, $key, $iv);
        }
        $this->err = 0;
        return $rt;
    }

    /**
     * 解密
     * @param string $ciphertext
     * @param string $key
     * @param string $iv
     * @return false|mixed|string 解密后字符串或数组
     */
    public function jdecrypt(string $ciphertext, string $key = '', string $iv = ''): mixed
    {
        $re = $this->decrypt($ciphertext, $key, $iv);
        if ($re === false) {
            $this->err = 1;  //解密失败
            return false;
        } else {
            $arr = json_decode($re, true);
            if (!empty($arr)) {
                $this->err = 0;
                return $arr;
            } else {
                $this->err = 2;  //非数组
                return $re;
            }
        }
    }


    /**
     * 生成TOKEN
     * @param array $jwt (exp: 过期时间, iat: 签发时间, nbf: 生效时间)
     * @param int $exp (0：为使用$jwt数据里的exp； 其它时间为有效期，如 600为10分钟)
     * @param bool|string $salt 是否需要（并使用盐值）生成签名，防止数据被篡改，提高安全性
     * @return bool|string 加密后字符串
     */
    public function getToken(array $jwt, int $exp = 0, bool|string $salt = false): bool|string
    {
        if($exp != 0){
            $jwt['exp'] = time() + $exp;
        }
        if ($salt || $salt === '') {
            $_salt = is_string($salt) ? $salt : null;
            $sign = $this->signature($jwt, $_salt);
            $jwt['sign'] = $sign;
        }
        $rt = $this->encrypt(json_encode($jwt));
        $this->err = 0;
        return $rt;

    }

    /**
     * 验证TOKEN
     * @param string $Token
     * @param bool|string|null $salt 需盐值验证签名，防止数据被篡改
     * @return bool|array Jwt数组 失败返回false, err为错误代码
     */
    public function verifyToken(string $Token, bool|string|null $salt = null): bool|array
    {
        $re = $this->decrypt($Token);
        if ($re === false) {
            $this->err = 1;  //解密失败
            return false;
        } else {
            $arr = json_decode($re, true);
            if (is_array($arr)) {
                if($salt !== false && !is_null($salt)){
                    if(!isset($arr['sign'])){
                        $this->err = 2; //签名错
                        return false;
                    }
                }
                if ($salt !== false && isset($arr['sign'])) {
                    $sign = $arr['sign'];
                    unset($arr['sign']);
                    $_salt = is_string($salt) ? $salt : null;
                    $_sign = $this->signature($arr, $_salt);
                    if ($sign != $_sign) {
                        $this->err = 2; //签名错，数据被篡改
                        return false;
                    }
                }

                $curTime = time();

                //签发时间大于当前服务器时间验证失败
                if (isset($arr['iat']) && $arr['iat'] > $curTime) {
                    $this->err = 3;
                    return false;
                }

                //过期时间小宇当前服务器时间验证失败
                if (isset($arr['exp']) && $arr['exp'] < $curTime) {
                    $this->err = 4;
                    return false;
                }

                //该nbf时间之前不接收处理该Token
                if (isset($arr['nbf']) && $arr['nbf'] > $curTime) {
                    $this->err = 5;
                    return false;
                }

                $this->err = 0;
                return $arr;
            } else {
                $this->err = 2;  //非数组
                return false;
            }
        }

    }


    protected function signature(array $arr, ?string $salt = null): string
    {
        unset($arr['sign']);
        ksort($arr);
        $_salt = is_null($salt) ? $this->salt : $salt;
        return md5(http_build_query($arr) . $_salt);

    }


    /**
     * base64UrlEncode   https://jwt.io/  中base64UrlEncode编码实现
     * @param string $input 需要编码的字符串
     * @return string
     */
    public function base64UrlEncode(string $input): string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * base64UrlEncode  https://jwt.io/  中base64UrlEncode解码实现
     * @param string $input 需要解码的字符串
     * @return bool|string
     */
    public function base64UrlDecode(string $input): bool|string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }


}