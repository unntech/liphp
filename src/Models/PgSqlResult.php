<?php
declare (strict_types = 1);

namespace LiPhp\Models;

class PgSqlResult extends DbResult
{
    protected mixed $returning  = null;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->returning  = $data['returning'] ?? null;
    }

    public function returning()
    {
        return $this->returning;
    }

    public function fetch_array(): bool|array
    {
        return pg_fetch_array($this->result);
    }

    public function fetch_object(): bool|object
    {
        return pg_fetch_object($this->result);
    }

    public function fetch_assoc(): bool|array
    {
        return pg_fetch_assoc($this->result);
    }

    public function num_rows(): int
    {
        return pg_num_rows($this->result);
    }

    public function num_fields(): int
    {
        return pg_num_fields($this->result);
    }

    public function fetch_row(): bool|array
    {
        return pg_fetch_row($this->result);
    }

    public function free_result(): bool
    {
        return @pg_free_result($this->result);
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