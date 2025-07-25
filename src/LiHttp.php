<?php
declare (strict_types = 1);

namespace LiPhp;

use LiPhp\Models\LiHttpResponse;

class LiHttp {

    protected static $instance;
    protected string $base_uri = '';
    protected int $timeout = -1;
    protected array $headers = [];

    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    public static function instance($options = []): static
    {
        if (is_null(self::$instance)) {
            static::$instance = new static($options);
        }else{
            static::$instance->setOptions($options);
        }

        return self::$instance;
    }

    public function setOptions(array $options = []): void
    {
        if(isset($options['base_uri'])){
            $this->base_uri = $options['base_uri'];
        }
        if(isset($options['timeout'])){
            $this->timeout = $options['timeout'];
        }
        if(isset($options['headers'])){
            $this->headers = $options['headers'];
        }
    }

    public function _get(string $uri = '', ?array $aHeader = null): LiHttpResponse
    {
        return $this->request('GET', $uri, null, $aHeader);
    }

    public function _post(string $uri = '', mixed $data = null, ?array $aHeader = null): LiHttpResponse
    {
        return $this->request('POST', $uri, $data, $aHeader);
    }

    public function request(string $method = 'GET', string $uri = '', mixed $data = null, ?array $aHeader = null ): LiHttpResponse
    {
        if(filter_var($uri, FILTER_VALIDATE_URL) !== false){
            $url = $uri;
        }else{
            $url = $this->base_uri . $uri;
        }
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HEADER, true );
        $pHeader = [];
        if(!empty($this->headers)){
            foreach($aHeader as $k=>$v){
                $pHeader[] = "{$k}: {$v}";
            }
        }
        if($aHeader != null){
            foreach($aHeader as $k=>$v){
                $pHeader[] = "{$k}: {$v}";
            }
        }
        if(!empty($pHeader)){
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $pHeader);
        }
        if($this->timeout >= 0){
            curl_setopt( $ch, CURLOPT_TIMEOUT, $this->timeout );
        }
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        if(strtolower(parse_url($url, PHP_URL_SCHEME)) == 'https'){
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
        }else{
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        }
        switch ($method){
            case 'POST':
                curl_setopt( $ch, CURLOPT_POST, true );
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'HEAD':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "HEAD");
                break;
            case 'OPTIONS':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "OPTIONS");
                break;
            case 'TRACE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "TRACE");
                break;
            default:

        }
        //curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0' );
        $response = curl_exec( $ch );
        if($response === false){
            $errorCode = curl_errno($ch);
            $errorMessage = curl_error($ch);
            $ret = new LiHttpResponse([
                'method'       => $method,
                'errorCode'    => $errorCode,
                'errorMessage' => $errorMessage,
            ]);
        }else{
            $responseInfo = curl_getinfo( $ch );
            $responseStatusCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
            if($method == 'HEAD'){
                $headers = $response;
                $responseBody = null;
            }else{
                // 分离header和body
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $headers = substr($response, 0, $header_size);
                $responseBody = substr($response, $header_size);
            }


            // 处理headers
            $header_lines = explode("\r\n", $headers);
            $header_array = [];
            foreach($header_lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $header_array[trim($key)] = trim($value);
                }
            }
            $responseHeaders = $header_array;
            $ret = new LiHttpResponse([
                'method'     => $method,
                'info'       => $responseInfo,
                'statusCode' => $responseStatusCode,
                'body'       => $responseBody,
                'headers'    => $responseHeaders,
            ]);
        }
        
        curl_close( $ch );
        return $ret;
    }

    /**
     * 设置 base_uri
     * @param string $uri
     * @return $this
     */
    public function setBaseUri(string $uri = ''): static
    {
        $this->base_uri = $uri;
        return $this;
    }

    /**
     * 设置请求头
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers = []): static
    {
        $this->headers = $headers;
        return $this;
    }

    public static function get(string $url, ?array $aHeader = null): bool|string
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        if($aHeader != null){
            foreach($aHeader as $k=>$v){
                $pHeader[] = "{$k}: {$v}";
            }
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $pHeader);
        }
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        if(strtolower(parse_url($url, PHP_URL_SCHEME)) == 'https'){
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
        }else{
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        }
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0' );
        $strRes = curl_exec( $ch );
        curl_close( $ch );
        return $strRes;
    }
    
    public static function post(string $url, mixed $data = null, ?array $aHeader = null): bool|string
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        if($aHeader != null){
            foreach($aHeader as $k=>$v){
                $pHeader[] = "{$k}: {$v}";
            }
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $pHeader);
        }
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        if(strtolower(parse_url($url, PHP_URL_SCHEME)) == 'https'){
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
        }else{
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        }
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0' );
        $strRes = curl_exec( $ch );
        curl_close( $ch );
        return $strRes;
    }

    /**
     * 获取请求来源头信息
     * @return array
     */
    public static function requestHeaders(): array
    {
        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $header = $result;
        } else {
            $header = [];
            $server = $_SERVER;
            foreach ($server as $key => $val) {
                if (0 === strpos($key, 'HTTP_')) {
                    $key          = str_replace('_', '-', strtolower(substr($key, 5)));
                    $header[$key] = $val;
                }
            }
            if (isset($server['CONTENT_TYPE'])) {
                $header['content-type'] = $server['CONTENT_TYPE'];
            }
            if (isset($server['CONTENT_LENGTH'])) {
                $header['content-length'] = $server['CONTENT_LENGTH'];
            }
        }

        return array_change_key_case($header);
    }
    
    /**
     * 发送HTTP状态码
     * @param int $status http 状态码
     * @return bool
     */
    public static function sendStatus(int $status): bool
    {
        $message = self::getStatusMessage($status);
        if(!headers_sent() && !empty($message)){
            if(substr(php_sapi_name(), 0, 3) == 'cgi'){//CGI 模式
                header("Status: $status $message");
            }else{ //FastCGI模式
                header("{$_SERVER['SERVER_PROTOCOL']} $status $message");
            }
            return true;
        }
        return false;
    }

    /**
     * 发送 HTTP 头部字符集
     * @param string $charset
     * @return bool 是否成功
     */
    public static function sendCharset(string $charset): bool
    {
        if(!headers_sent()){
            header('Content-Type:text/html; charset='.$charset);
            return true;
        }
        return false;
    }

    /**
     * 获取HTTP状态码对应描述
     * @param int $status
     * @return string|null
     */
    public static function getStatusMessage(int $status): ?string
    {
        static $msg = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded',
        ];
        return $msg[$status] ?? null;
    }

    /**
     * HTTP方式跳转
     * @param string $url 跳转路径
     * @param bool $permanently 是否为长期资源重定向
     */
    public static function redirect(string $url, bool $permanently = false)
    {
        self::sendStatus($permanently ? 301 : 302);
        header('Location:'.$url);
        exit(0);
    }

    /**
     * 获取域名SSL证书信息
     * @param string $domain
     * @param int $port
     * @return array|false
     */
    public static function getCertificateInformation(string $domain, int $port=443): bool|array
    {
        if(empty($domain)){
            return false;
        }
        // 创建一个流上下文，其中包含一个ssl选项，该选项用于捕获对等证书。
        $streamContext = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
        // 使用stream_socket_client函数建立一个安全的SSL连接。连接的目标是在指定的域名后面的端口443上建立连接。函数的参数包括域名，错误号，错误消息，超时时间，连接选项和流上下文。
        $secureConnection = stream_socket_client("ssl://{$domain}:{$port}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $streamContext);

        if (!$secureConnection) {
            return false;
        }
        // 获取流上下文参数中的SSL信息，并将其赋值给$sslInfo变量。
        $sslInfo = stream_context_get_params($secureConnection);
        //使用openssl_x509_parse函数来解析传入的SSL证书。它首先从$sslInfo数组中获取"options"下的"ssl"数组，然后从中获取"peer_certificate"字段的值，即对等证书。然后将该证书作为参数传递给openssl_x509_parse函数，该函数将解析证书并返回一个关联数组$certInfo，包含了证书的各种信息。
        $certInfo = openssl_x509_parse($sslInfo["options"]["ssl"]["peer_certificate"]);

        return $certInfo;
    }

    /**
     * 获取域名SSL证书有效期
     * @param string $domain
     * @param int $port
     * @return false|mixed
     */
    public static function getCertificateExpirationTime(string $domain, int $port=443)
    {
        $certInfo = self::getCertificateInformation($domain, $port);
        if($certInfo === false){
            return false;
        }

        return $certInfo["validTo_time_t"] ?? false;
    }
}