<?php
declare (strict_types = 1);

namespace LiPhp\Models;

class SqlSrvResult extends DbResult
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function fetch_assoc(): bool|array|null
    {
        return sqlsrv_fetch_array( $this->result, SQLSRV_FETCH_ASSOC );
    }

    public function fetch_array(): bool|array|null
    {
        return sqlsrv_fetch_array( $this->result, SQLSRV_FETCH_BOTH);
    }

    public function fetch_row(): bool|array|null
    {
        return sqlsrv_fetch_array( $this->result, SQLSRV_FETCH_NUMERIC );
    }

    public function has_rows(): bool
    {
        return sqlsrv_has_rows($this->result);
    }
    public function num_rows(): bool|int
    {
        return sqlsrv_num_rows($this->result);
    }

    public function num_fields(): bool|int
    {
        return sqlsrv_num_fields($this->result);
    }

    public function free_result(): bool
    {
        return sqlsrv_free_stmt( $this->result );
    }

    /**
     * 查询结果转数组
     * @param string $indexField
     * @param string|null $value
     * @return array
     */
    public function toArray(string $indexField='', ?string $value = null): array
    {
        $ret = [];
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