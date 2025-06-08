<?php
declare (strict_types = 1);

namespace LiPhp\Models;

class Result
{
    /**
     * 操作是否成功
     * @var bool
     */
    public readonly bool $success;

    /**
     * 返回数据
     * @var mixed
     */
    protected mixed $data;

    /**
     * 错误代码
     * @var int
     */
    public readonly int $errCode;

    /**
     * 错误信息
     * @var string
     */
    public readonly string $message;

    /**
     * 构造函数
     * @param bool $success
     * @param mixed|null $data
     * @param int $errCode
     * @param string $message
     */
    private function __construct(bool $success, mixed $data = null, int $errCode = 0, string $message = '')
    {
        $this->success = $success;
        $this->data = $data;
        $this->errCode = $errCode;
        $this->message = $message;
    }

    /**
     * 创建成功结果
     * @param mixed|null $data
     * @param string $message
     * @return Result
     */
    public static function success(mixed $data = null, string $message = '操作成功'): Result
    {
        return new static(true, $data, 0, $message);
    }

    /**
     * 创建失败结果
     * @param int $errCode
     * @param string $message
     * @param mixed|null $data
     * @return Result
     */
    public static function error(int $errCode = 500, string $message = '操作失败', mixed $data = null): Result
    {
        return new static(false, $data, $errCode, $message);
    }

    /**
     * 创建自定义结果
     * @param bool $success
     * @param mixed|null $data
     * @param int $errCode
     * @param string $message
     * @return Result
     */
    public static function custom(bool $success, mixed $data = null, int $errCode = 0, string $message = ''): Result
    {
        return new static($success, $data, $errCode, $message);
    }

    /**
     * 转换为数组
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'errCode' => $this->errCode,
            'message' => $this->message,
            'data'    => $this->data,
        ];
    }

    /**
     * 转换为JSON字符串
     * @param int $options
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->toArray(), $options);
    }

    // Getter 方法
    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getData()
    {
        return $this->data;
    }

    public function errCode(): int
    {
        return $this->errCode;
    }

    public function message(): string
    {
        return $this->message;
    }
}