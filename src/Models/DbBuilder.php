<?php
declare (strict_types = 1);

namespace LiPhp\Models;

class DbBuilder
{
    protected $connid;
    protected string $sql = '';
    protected array $options = ['table'=>'', 'alias'=> null, 'fields'=>null, 'condition'=>null, 'param'=>[], 'fetchSql'=>false];
    protected bool $query_finished = false;
    protected int $errorCode = 0;
    protected string $errorMessage = '';

    /**
     * 返回当前SQL语句
     * @return string
     */
    public function getLastSql(): string
    {
        return $this->sql;
    }

    /**
     * 面向对象 链式操作
     * @param string $table 数据库表名
     * @param string|null $alias
     * @return bool|$this
     */
    public function table(string $table, ?string $alias= null): bool|static
    {
        if(empty($table)){
            return false;
        }
        $table = preg_replace('/[^A-Za-z0-9_\.`]/', '', $table);
        $this->options = ['table'=>"{$table}", 'alias'=>$alias, 'fields'=>null, 'condition'=>null, 'param'=>[], 'fetchSql'=>false];
        $this->query_finished = false;
        return $this;
    }

    /**
     * 表别名
     * @param string $alias
     * @return $this
     */
    public function alias(string $alias): static
    {
        $this->options['alias'] = $alias;
        return $this;
    }

    /**
     * 只构建SQL，不执行
     * @param bool $fetch
     * @return $this
     */
    public function fetchSql(bool $fetch = true): static
    {
        $this->options['fetchSql'] = $fetch;
        return $this;
    }

    /**
     * 更新的字段及值
     * @param string|array $fields 示例：['field1'=>1,'field2'=>'abc', 'field3'=>true]
     * @return $this
     */
    public function fields(string|array $fields): static
    {
        $this->options['fields'] = $this->_fieldsAddAlais($fields);
        return $this;
    }

    /**
     * 条件规则 默认无，即全部
     * $condition 若为数组则按条件规则，示例：['id'=>1]  //id=1
     * ['id'=>1,'m'=>2] //id = 1 AND m = 2
     * ['id'=>['>',1], 'fed'=>['LIKE','S%']] //id > 1 and fed LIKE 'S%'
     * @param string|array $condition
     * @return $this
     */
    public function where(string|array $condition): static
    {
        if(is_array($condition)){
            if(!empty($this->options['condition']) && !is_array($this->options['condition'])){
                $this->options['condition'] = [];
            }
            if($this->_array_is_list($condition)){
                if(empty($this->options['condition']) || !is_array($this->options['condition'])){
                    $this->options['condition'] = $condition;
                }else{
                    $this->options['condition'] = array_merge($this->options['condition'], $condition);
                }
            }else{
                $this->options['condition'][] = $condition;
            }
        }else{
            if(is_array($this->options['condition'])){
                $this->options['condition'][] = $condition;
            }else{
                $this->options['condition'] = $condition;
            }
        }
        return $this;
    }

    /**
     * $param = [
     *      'JOIN'=>'LEFT JOIN table as b ON a.id = b.id',
     *      'GROUPBY'=>'cls',
     *      'ORDER'=>'id desc',
     *      'LIMIT'=>[0,10]
     * ]
     * @param array $param
     * @return $this
     */
    public function param(array $param): static
    {
        $this->options['param'] = $param;
        return $this;
    }

    public function limit(int|array|null $limit): static
    {
        $this->options['param']['LIMIT'] = $limit;
        return $this;
    }

    public function join(?string $join): static
    {
        $this->options['param']['JOIN'] = $join;
        return $this;
    }

    public function groupby(string|array|null $groupby): static
    {
        $this->options['param']['GROUPBY'] = $groupby;
        return $this;
    }

    public function order(string|array|null $order): static
    {
        $this->options['param']['ORDER'] = $order;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    protected function _fieldsAddAlais($fields)
    {
        if(empty($this->options['alias'])){
            return $fields;
        }
        $ct = gettype($fields);
        switch ($ct){
            case 'array':
                $_fields = [];
                foreach($fields as $k=>$v){
                    if(!str_contains($v, '.')){
                        $_fields[] = $this->options['alias'] .'.'.$v;
                    }else{
                        $_fields[] = $v;
                    }
                }
                $fields = $_fields;
                break;
            case 'string':
                if(!str_contains($fields, '.')) {
                    $fields = $this->options['alias'] . '.' . $fields;
                }
                break;
        }

        return $fields;
    }

    protected function _array_is_list(array $arr): bool
    {
        if(function_exists('array_is_list')) {
            return array_is_list($arr);
        }else{
            return $this->__array_is_list($arr);
        }
    }
    private function __array_is_list(array $arr): bool
    {
        $i = 0;
        foreach ($arr as $k => $v) {
            if ($k !== $i++) {
                return false;
            }
        }
        return true;
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