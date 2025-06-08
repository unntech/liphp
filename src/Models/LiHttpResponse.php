<?php
declare (strict_types = 1);

namespace LiPhp\Models;

class LiHttpResponse
{
    protected ?string $body = null;
    protected mixed $info = [];
    protected array $headers = [];
    protected int $statusCode = 0;
    protected int $errorCode = 0;
    protected string $errorMessage = '';
    protected string $method = '';

    public function __construct($data = [])
    {
        $this->method = $data['method'] ?? '';
        $this->body = $data['body'] ?? null;
        $this->info = $data['info'] ?? [];
        $this->headers = $data['headers'] ?? [];
        $this->statusCode = $data['statusCode'] ?? 0;
        $this->errorCode = $data['errorCode'] ?? 0;
        $this->errorMessage = $data['errorMessage'] ?? '';
    }

    public function __toString()
    {
        return $this->body;
    }

    /**
     * 获取返回结果
     * @param bool $AutomaticParsing 根据响应结果Content-Type自动判定数据类型进行解析
     * @return array|mixed|void|null
     */
    public function getBody(bool $AutomaticParsing = false)
    {
        if($AutomaticParsing){
            $string = $this->getContentType();
            if(str_contains($string, 'application/json')){
                return $this->getBodyDecodeJson();
            }
            if((str_contains($string, 'application/xml')) || (str_contains($string, 'text/xml'))){
                return $this->getBodyDecodeXml();
            }
        }

        return $this->body;
    }

    /**
     * 获取返回结果并将JSON解析成数组
     * @return mixed
     */
    public function getBodyDecodeJson(): mixed
    {
        return json_decode($this->body, true);
    }

    public function getBodyDecodeXml(): bool|array
    {
        $r = simplexml_load_string($this->body);
        return $r === false ? false : (array)$r;
    }

    /**
     * 获取响应信息
     * @return mixed|array
     */
    public function getInfo(): mixed
    {
        return $this->info;
    }

    /**
     * 获取响应头信息
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * 获取响应头数据
     * @param string $header
     * @return mixed|string
     */
    public function getHeader(string $header): mixed
    {
        return $this->headers[$header] ?? '';
    }

    /**
     * 请求响应状态
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 结果内容类型
     * @return string
     */
    public function getContentType(): string
    {
        return $this->info['content_type'] ?? '';
    }

    /**
     * 请求的URL地址
     * @return string
     */
    public function url(): string
    {
        return $this->info['url'] ?? '';
    }

    /**
     * 请求错误代码，成功为0
     * @return int
     */
    public function errorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * 请求错误信息
     * @return string
     */
    public function errorMessage(): string
    {
        return $this->errorMessage;
    }

}