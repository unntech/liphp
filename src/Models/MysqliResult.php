<?php
declare (strict_types = 1);

namespace LiPhp\Models;

class MysqliResult extends DbResult
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function fetch_array(): bool|array|null
    {
        return mysqli_fetch_array($this->result);
    }

    public function fetch_assoc(): bool|array|null
    {
        return mysqli_fetch_assoc($this->result);
    }

    public function num_rows(): int|string
    {
        return mysqli_num_rows($this->result);
    }

    public function num_fields(): int
    {
        return mysqli_num_fields($this->result);
    }

    public function fetch_row(): bool|array|null
    {
        return mysqli_fetch_row($this->result);
    }

    public function free_result(): void
    {
        @mysqli_free_result($this->result);
    }

    /**
     * 查询结果转数组
     * @param string $indexField
     * @param string|null $value
     * @return array
     */
    public function toArray(string $indexField='', ?string $value = null): array
    {
        $ret = array();
        if($this->errorCode != 0){
            throw new \RuntimeException($this->errorMessage(), $this->errorCode());
        }
        while($r = $this->fetch_assoc()){
            if(empty($value)){
                $_r = $r;
            }else{
                $_r = $r[$value];
            }
            if($indexField=='' || !isset($r[$indexField])){
                $ret[] = $_r;
            }else{
                $ret[$r[$indexField]] = $_r;
            }
        }
        return $ret;
    }
}