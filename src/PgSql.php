<?php
declare (strict_types = 1);

namespace LiPhp;

use LiPhp\Models\PgSqlResult;
use LiPhp\Models\DbBuilder;

class PgSql extends DbBuilder
{
    protected int $insertid = 0;
    protected int $affected_rows = 0;
    protected string $schema = 'public';
    protected mixed $returning  = null;

    /**
     * 构造方法
     * @access public
     */
    public function __construct(array $cfg)
    {
        return $this->connect($cfg['hostname'], $cfg['hostport'], $cfg['username'], $cfg['password'], $cfg['dbname'], $cfg['charset'], $cfg['default_schema']);
    }

    public function connect($dbhost, $dbport, $dbuser, $dbpass, $dbname='', $dbcharset='', $default_schema='public')
    {
        if ( !$this->connid = pg_connect("host={$dbhost} port={$dbport} dbname={$dbname} user={$dbuser} password={$dbpass}") ) {
            $this->halt( 'Can not connect to PgSql server' );
        }
        if(!empty($dbcharset)){
            pg_set_client_encoding($this->connid, $dbcharset);
        }
        if(!empty($default_schema)){
            pg_query($this->connid, "SET search_path TO {$default_schema}");
        }
        $this->schema = $default_schema;
        return $this->connid;
    }

    /**
     * $returning 设为 ''时，在 insert 时不调用 RETURNING
     * @param string $returning
     * @return $this
     */
    public function returning(string $returning = 'id'): static
    {
        $this->options['returning'] = $returning;
        return $this;

    }

    public function default_schema(string $schema)
    {
        return pg_query($this->connid, "SET search_path TO {$schema}");
    }

    protected function _query(string $sql)
    {
        $this->sql = $sql;
        try {
            $this->insertid = 0;
            $this->returning = null;
            if ( preg_match( "/ RETURNING /i", $sql ) ) {
                $query = pg_query( $this->connid, $sql );
                if($query){
                    $this->returning = pg_fetch_assoc($query);
                }
            } else {
                $query = pg_query($this->connid, $sql);
            }
        }catch (\Throwable $e){
            $this->exception($e, $sql);
        }
        // 获取错误号
        if($query){
            $error_code =(int)pg_result_error_field($query, PGSQL_DIAG_SQLSTATE);
            $this->errorCode = $error_code;
            $this->affected_rows = pg_affected_rows($query);
        }else{
            $this->errorCode = -1;
        }
        $this->errorMessage = $this->error();
        $this->query_finished = true;
        return $query;
    }

    public function query(string $sql): PgSqlResult
    {
        $query = $this->_query($sql);
        return $this->result($query);
    }

    public function result($query): PgSqlResult
    {
        return PgSqlResult::instance([
            'result'        => $query,
            'sql'           => $this->sql,
            'returning'      => $this->returning,
            'affected_rows' => $this->affected_rows(),
            'errorCode'     => $this->errorCode,
            'errorMessage'  => $this->errorMessage,
        ]);
    }

    public function schema(string $schema): static
    {
        $this->options['schema'] = $schema;
        return $this;
    }

    //事务操作
    public function startTrans()
    {
        return $this->_query('START TRANSACTION');
    }

    public function commit()
    {
        return $this->_query('COMMIT');
    }

    public function rollback()
    {
        return $this->_query('ROLLBACK');
    }

    //使用buildSql构造子查询
    public function buildSql(): string
    {
        $this->options['fetchSql'] =  true;
        $res = $this->select();
        return '( ' . $res . ' )';
    }

    public function update(?array $data = null)
    {
        $table = $this->options['table'];
        if(empty($table) || $this->query_finished !== false){ //未设置表名
            return false;
        }
        $fields = is_array($data) ? $data : $this->options['fields'];
        $condition = $this->options['condition'];

        $table = $this->tableNameAssociation($table);
        if(!is_array($fields) || empty($fields)){
            return false;
        }
        $this->sql = '';
        $ufields = $this->_fields_strip($fields);
        if(empty($ufields)){
            return false;
        }
        $sql = "UPDATE \"{$table}\" SET " . implode(', ', $ufields);
        if(!empty($condition)){
            $ct = gettype($condition);
            if($ct == 'string'){
                $sql .= " WHERE " . $condition;
            }elseif($ct == 'array'){
                $cons = $this->_condition_strip($condition);
                $sql .= " WHERE " . implode(' AND ', $cons);
            }else{
                //条件参数类型不正常
                $this->sql = 'condition type error';
                return false;
            }

        }
        if(!empty($this->options['returning'])){
            $sql .= " RETURNING " . $this->options['returning'];
        }
        if($this->options['fetchSql']){ return $sql; }
        $res = $this->_query($sql);
        return PgSqlResult::instance([
            'result'        => $res,
            'sql'           => $this->sql,
            'returning'      => $this->returning,
            'affected_rows' => $this->affected_rows(),
            'errorCode'     => $this->errorCode,
            'errorMessage'  => $this->errorMessage,
        ]);
    }

    public function delete()
    {
        $table = $this->options['table'];
        if(empty($table) || $this->query_finished !== false){ //未设置表名
            return false;
        }
        $condition = $this->options['condition'];

        $table = $this->tableNameAssociation($table);
        $this->sql = '';
        $sql = "DELETE FROM \"{$table}\" ";
        if(!empty($condition)){
            $ct = gettype($condition);
            if($ct == 'string'){
                $sql .= " WHERE " . $condition;
            }elseif($ct == 'array'){
                $cons = $this->_condition_strip($condition);
                $sql .= " WHERE " . implode(' AND ', $cons);
            }else{
                //条件参数类型不正常
                $this->sql = 'condition type error';
                return false;
            }

        }
        if(!empty($this->options['returning'])){
            $sql .= " RETURNING " . $this->options['returning'];
        }
        if($this->options['fetchSql']){ return $sql; }
        $res = $this->_query($sql);
        return PgSqlResult::instance([
            'result'        => $res,
            'sql'           => $this->sql,
            'returning'      => $this->returning,
            'affected_rows' => $this->affected_rows(),
            'errorCode'     => $this->errorCode,
            'errorMessage'  => $this->errorMessage,
        ]);
    }

    /**
     * 插入数据
     * @param array $data
     * @param bool $returnResult
     * @return false|int|string|PgSqlResult
     */
    public function insert(array $data = [], bool $returnResult = false)
    {
        $table = $this->options['table'];
        if(empty($table) || $this->query_finished !== false){ //未设置表名
            return false;
        }

        $table = $this->tableNameAssociation($table);
        $this->sql = '';
        if(empty($data)){
            return false;
        }
        $d = $this->_fields_split($data);
        $sql = "INSERT INTO \"{$table}\" (" . implode(',', $d[0]) . ") VALUES (" . implode(',', $d[1]) .") ";
        $_returning = $this->options['returning'] ?? 'id';
        if($_returning != ''){
            $sql .= " RETURNING " . $_returning;
        }
        if($this->options['fetchSql']){ return $sql; }

        $res = $this->_query($sql);
        if(!empty($this->returning)){
            $this->insertid = (int)$this->returning[$_returning];
        }
        if($returnResult){
            return PgSqlResult::instance([
                'result'        => $res,
                'sql'           => $this->sql,
                'returning'     => $this->returning,
                'insertId'      => $this->insert_id(),
                'affected_rows' => $this->affected_rows(),
                'errorCode'     => $this->errorCode,
                'errorMessage'  => $this->errorMessage,
            ]);
        }else{
            if($res){
                return $this->insert_id();
            }else{
                return 0;
            }
        }
    }

    /**
     * 批量插入数据
     * @param array $data
     * @return false|PgSqlResult|string
     */
    public function insertAll(array $data = [])
    {
        $table = $this->options['table'];
        if(empty($table) || $this->query_finished !== false){ //未设置表名
            return false;
        }

        $table = $this->tableNameAssociation($table);
        $this->sql = '';
        $d = [];
        foreach($data as $da){
            if(!is_array($da)){
                continue;
            }
            $d[] = $this->_fields_split($da);
        }
        if(empty($d)){
            return false;
        }

        $sql = "INSERT INTO \"{$table}\" (" . implode(',', $d[0][0]) . ") VALUES ";
        $first = true;
        foreach($d as $di){
            if(!$first){ $sql .= ', ';}
            $sql .= "(" . implode(',', $di[1]) . ") ";
            $first = false;
        }
        $_returning = $this->options['returning'] ?? 'id';
        if($_returning != ''){
            $sql .= " RETURNING " . $_returning;
        }
        if($this->options['fetchSql']){ return $sql; }

        $res = $this->_query($sql) ;
        if(!empty($this->returning)){
            $this->insertid = (int)$this->returning[$_returning];
        }
        return PgSqlResult::instance([
            'result'        => $res,
            'sql'           => $this->sql,
            'returning'     => $this->returning,
            'insertId'      => $this->insert_id(),
            'affected_rows' => $this->affected_rows(),
            'errorCode'     => $this->errorCode,
            'errorMessage'  => $this->errorMessage,
        ]);
    }

    public function select()
    {
        $table = $this->options['table'];
        if(empty($table) || $this->query_finished !== false){ //未设置表名
            return false;
        }
        $fields = $this->options['fields'];
        $condition = $this->options['condition'];
        $param = $this->options['param'];
        $alias = $this->options['alias'];

        $table = $this->tableNameAssociation($table);
        $this->sql = '';
        if(empty($fields)){
            $sql = "SELECT ".(empty($alias) ? '' : "{$alias}.")."* FROM \"{$table}\" ";
        }else{
            $ct = gettype($fields);
            if($ct == 'string'){
                $fields = preg_replace('/[^A-Za-z0-9_,\-\. `"()\*]/', '', $fields);
                $sql = "SELECT {$fields} FROM \"{$table}\" ";
            }elseif($ct == 'array'){
                $sql = "SELECT ". implode(',', $fields) ." FROM \"{$table}\" ";
            }else{
                $sql = "SELECT noFields FROM \"{$table}\" ";
            }
        }
        if(!empty($alias)){
            $sql .= "AS {$alias} ";
        }

        if(!empty($param['JOIN'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. `"=]/', '', $param['JOIN']);
            $sql .= " {$str} ";
        }

        if(!empty($condition)){
            $ct = gettype($condition);
            if($ct == 'string'){
                $sql .= " WHERE " . $condition;
            }elseif($ct == 'array'){
                $cons = $this->_condition_strip($condition);
                $sql .= " WHERE " . implode(' AND ', $cons);
            }else{
                //条件参数类型不正常
                $this->sql = 'condition type error';
                return false;
            }
        }

        if(!empty($param['GROUPBY'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. "`]/', '', $param['GROUPBY']);
            $sql .= " GROUP BY " .$str;
        }
        if(!empty($param['ORDER'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. "`]/', '', $param['ORDER']);
            $sql .= " ORDER BY " .$str;
        }
        if(!empty($param['LIMIT'])){
            if(is_array($param['LIMIT'])){
                $sql .= " LIMIT ".intval($param['LIMIT'][1]).' OFFSET '.intval($param['LIMIT'][0]);
            }else{
                $sql .= " LIMIT ".intval($param['LIMIT']);
            }
        }
        if($this->options['fetchSql']){ return $sql; }

        $res = $this->_query($sql) ;
        return PgSqlResult::instance([
            'result'        => $res,
            'sql'           => $this->sql,
            'errorCode'     => $this->errorCode,
            'errorMessage'  => $this->errorMessage,
        ]);
    }


    /**
     * 查询一条数据
     * @return array|false|string|null
     */
    public function selectOne()
    {
        $table = $this->options['table'];
        if(empty($table) || $this->query_finished !== false){ //未设置表名
            return false;
        }
        $fields = $this->options['fields'];
        $condition = $this->options['condition'];
        $param = $this->options['param'];
        $alias = $this->options['alias'];

        $table = $this->tableNameAssociation($table);
        $this->sql = '';
        if(empty($fields)){
            $sql = "SELECT ".(empty($alias) ? '' : "{$alias}.")."* FROM \"{$table}\" ";
        }else{
            $ct = gettype($fields);
            if($ct == 'string'){
                $fields = preg_replace('/[^A-Za-z0-9_,\-\. "`()\*]/', '', $fields);
                $sql = "SELECT {$fields} FROM \"{$table}\" ";
            }elseif($ct == 'array'){
                $sql = "SELECT ". implode(',', $fields) ." FROM \"{$table}\" ";
            }else{
                $sql = "SELECT Fields FROM \"{$table}\" ";
            }
        }

        if(!empty($alias)){
            $sql .= "AS {$alias} ";
        }

        if(!empty($param['JOIN'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. "`=]/', '', $param['JOIN']);
            $sql .= " {$str} ";
        }

        if(!empty($condition)){
            $ct = gettype($condition);
            if($ct == 'string'){
                $sql .= " WHERE " . $condition;
            }elseif($ct == 'array'){
                $cons = $this->_condition_strip($condition);
                $sql .= " WHERE " . implode(' AND ', $cons);
            }else{
                //条件参数类型不正常
                $this->sql = 'condition type error';
                return false;
            }
        }

        if(!empty($param['GROUPBY'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. "`]/', '', $param['GROUPBY']);
            $sql .= " GROUP BY " .$str;
        }
        if(!empty($param['ORDER'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. "`]/', '', $param['ORDER']);
            $sql .= " ORDER BY " .$str;
        }

        $sql .= " LIMIT 1";

        if($this->options['fetchSql']){ return $sql; }

        $query = $this->_query($sql);
        return pg_fetch_assoc($query);
    }

    public function getOne()
    {
        return $this->selectOne();
    }

    public function getValue()
    {
        $table = $this->options['table'];
        if(empty($table) || $this->query_finished !== false){ //未设置表名
            return false;
        }
        $fields = $this->options['fields'];
        $condition = $this->options['condition'];
        $param = $this->options['param'];
        $alias = $this->options['alias'];

        $table = $this->tableNameAssociation($table);
        $this->sql = '';
        $ct = gettype($fields);
        if(empty($fields) || $ct != 'string'){
            return false;
        }
        $fields = preg_replace('/[^A-Za-z0-9_,\-\. "`()\*]/', '', $fields);
        $sql = "SELECT {$fields} FROM \"{$table}\" ";

        if(!empty($alias)){
            $sql .= "AS {$alias} ";
        }

        if(!empty($param['JOIN'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. "`=]/', '', $param['JOIN']);
            $sql .= " {$str} ";
        }

        if(!empty($condition)){
            $ct = gettype($condition);
            if($ct == 'string'){
                $sql .= " WHERE " . $condition;
            }elseif($ct == 'array'){
                $cons = $this->_condition_strip($condition);
                $sql .= " WHERE " . implode(' AND ', $cons);
            }else{
                //条件参数类型不正常
                $this->sql = 'condition type error';
                return false;
            }
        }

        if(!empty($param['GROUPBY'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. "`]/', '', $param['GROUPBY']);
            $sql .= " GROUP BY " .$str;
        }
        if(!empty($param['ORDER'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. "`]/', '', $param['ORDER']);
            $sql .= " ORDER BY " .$str;
        }

        $sql .= " LIMIT 1";

        $this->sql = $sql;
        if($this->options['fetchSql']){ return $sql; }

        $query = $this->_query($sql);
        $r = pg_fetch_row($query);
        return $r ? $r[0] : null;
    }

    public function count()
    {
        $table = $this->options['table'];
        if(empty($table) || $this->query_finished !== false){ //未设置表名
            return false;
        }
        $condition = $this->options['condition'];
        $param = $this->options['param'];
        $alias = $this->options['alias'];

        $table = $this->tableNameAssociation($table);
        $this->sql = '';
        $sql = "SELECT COUNT(*) AS amount FROM \"{$table}\" ";
        if(!empty($alias)){
            $sql .= "AS {$alias} ";
        }

        if(!empty($param['JOIN'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. "`=]/', '', $param['JOIN']);
            $sql .= " {$str} ";
        }
        if(!empty($condition)){
            $ct = gettype($condition);
            if($ct == 'string'){
                $sql .= " WHERE " . $condition;
            }elseif($ct == 'array'){
                $cons = $this->_condition_strip($condition);
                $sql .= " WHERE " . implode(' AND ', $cons);
            }else{
                //条件参数类型不正常
                $this->sql = 'condition type error';
                return false;
            }

        }
        $res = $this->_query($sql);
        $r = pg_fetch_assoc($res);
        return $r ? (int)$r['amount'] : 0;
    }

    public function affected_rows()
    {
        return $this->affected_rows;
    }
    public function insert_id()
    {
        return $this->insertid;
    }

    public function version(): string
    {
        return pg_version($this->connid)['server'];
    }

    public function close(): bool
    {
        return pg_close($this->connid);
    }

    public function error(): string
    {
        return @pg_last_error($this->connid);
    }

    public function errno(): int
    {
        return $this->errorCode;
    }

    protected function halt($message = '', $sql = '')
    {
        if(defined('DT_DEBUG') && DT_DEBUG){
            echo "\t\t<query>".$sql."</query>\n\t\t<errno>".$this->errno()."</errno>\n\t\t<errmsg>".$message."</errmsg>\n";
        }else{
            echo $message;
        }
    }

    protected function exception(\Throwable $e, $sql)
    {
        if (defined('DT_DEBUG') && DT_DEBUG) {
            $html = '<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"><title>HTTP 500</title><style>body{margin: 0 auto;} .header{background: #6c757d; color: #eee; padding: 50px 15px 30px 15px;line-height: 1.5rem} .sql{background: #cce5ff; color: #004085; padding: 15px 15px;line-height: 1.5rem} .msg{padding: 15px 15px;line-height: 1.25rem}</style></head><body>';
            $html .= '<div class="header"><h3>' . $e->getMessage() . '</h3>Code: ' . $e->getCode() . '<BR>File: ' . $e->getFile() . '<BR>Line: ' . $e->getLine() . '</div>';
            $html .= '<div class="sql">Sql: ' .$sql. '</div>';
            $html .= '<div class="msg"><pre>' . print_r($e, true) . '</pre></div>';
            $html .= '</body></html>';
            echo $html;
        } else {
            $msg = $e->getCode() . ': ' . $e->getMessage();
            $html = '<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"><title>HTTP 500</title><style>body{background-color:#444;font-size:16px;}h3{font-size:32px;color:#eee;text-align:center;padding-top:50px;font-weight:normal;}</style></head>';
            $html .= '<body><h3>' . $msg . '</h3></body></html>';
            echo $html;
        }
        exit(0);
    }

    /*
     * 检查是否为注入
     * 未完工 2022-10-20
     */
    public function IsInjection($str)
    {
        $isInj = false;
        // /((\%3D)|(=))[^\n]*((\%27)|(\’)|(\-\-)|(\%3B)|(:))/i
        $Exec_Commond = "/(\s|\S)*(exec(\s|\+)+(s|x)p\w+)(\s|\S)*/";
        $Simple_XSS = "/(\s|\S)*((%3C)|)(\s|\S)*/";
        $Eval_XSS = "/(\s|\S)*((%65)|e)(\s)*((%76)|v)(\s)*((%61)|a)(\s)*((%6C)|l)(\s|\S)*/";
        $Image_XSS = "/(\s|\S)*((%3C)|)(\s|\S)*/" ;
        $Script_XSS = "/(\s|\S)*((%73)|s)(\s)*((%63)|c)(\s)*((%72)|r)(\s)*((%69)|i)(\s)*((%70)|p)(\s)*((%74)|t)(\s|\S)*/";
        $SQL_Injection = "/(\s|\S)*((%27)|(')|(%3D)|(=)|(/)|(%2F)|(\")|((%22)|(-|%2D){2})|(%23)|(%3B)|(;))+(\s|\S)*/";
        if(preg_match($Exec_Commond, $str)){
            $isInj = true;
        }
        if(preg_match($Simple_XSS, $str)){
            $isInj = true;
        }
        if(preg_match($Eval_XSS, $str)){
            $isInj = true;
        }
        if(preg_match($Image_XSS, $str)){
            $isInj = true;
        }
        if(preg_match($Script_XSS, $str)){
            $isInj = true;
        }
        return $isInj;
    }

    public function removeEscape($str)
    {
        $str = str_replace(array('\'','"','\\'),"",$str);
        return $str;
    }

    public function escape_string($str)
    {
        return pg_escape_string($this->connid, $str);
    }

    protected function _fields_strip($fields)
    {
        $ufields = [];
        foreach($fields as $k=>$v){
            //var_dump(gettype($v));
            switch(gettype($v)){
                case 'string':
                    $ufields[] = $k . " = '" .$this->escape_string($v). "' ";
                    break;
                case 'integer':
                    $ufields[] = "{$k} = {$v} ";
                    break;
                case 'double':
                    $ufields[] = "{$k} = {$v} ";
                    break;
                case 'boolean':
                    $_v = $v ? '1 ' : '0 ';
                    $ufields[] = "{$k} = ". $_v ;
                    break;
                case 'array':
                    $_v0 = strtoupper($v[0]);
                    switch ($_v0){
                        case 'INC':
                            $ufields[] = "{$k} = {$k} + {$v[1]}";
                            break;
                        case 'DEC':
                            $ufields[] = "{$k} = {$k} - {$v[1]}";
                            break;
                        default:
                    }
                    break;
                case 'NULL':
                    $ufields[] = "{$k} = NULL ";
                    break;
                default:
                    return false;
                    break;
            }
        }

        return $ufields;
    }

    protected function _fields_split($fields)
    {
        $fk = [];
        $fv = [];
        foreach($fields as $k=>$v){
            switch(gettype($v)){
                case 'string':
                    $fk[] = "\"{$k}\"";
                    $fv[] = "'" .$this->escape_string($v). "'";
                    break;
                case 'integer':
                    $fk[] = "\"{$k}\"";
                    $fv[] = "'{$v}'";
                    break;
                case 'double':
                    $fk[] = "\"{$k}\"";
                    $fv[] = "'{$v}'";
                    break;
                case 'boolean':
                    $_v = $v ? '1 ' : '0 ';
                    $fk[] = "\"{$k}\"";
                    $fv[] = "'{$_v}'";
                    break;
                case 'NULL':
                    $fk[] = "\"{$k}\"";
                    $fv[] = 'NULL';
                    break;
                default:
                    $fk[] = "\"{$k}\"";
                    $fv[] = 'NULL';
                    break;
            }
        }

        return [$fk, $fv];
    }



    protected function _condition_strip($condition)
    {
        if($this->_array_is_list($condition)){
            $cons = [];
            foreach ($condition as $kk=>$cc){
                $_cons = $this->__condition_strip($cc);
                foreach ($_cons as $k=>$v){
                    $cons[] = $v;
                }
            }
            return  $cons;
        }else{
            return $this->__condition_strip($condition);
        }
    }

    protected function __condition_strip($condition)
    {
        $cons = [];
        if(is_array($condition)){
            foreach($condition as $k=>$v){
                if(false === strpos($k, '.') && !empty($this->options['alias'])){
                    $k = $this->options['alias'] . '.' . $k;
                }
                switch(gettype($v)){
                    case 'string':
                        $cons[] = $k . " = '" .$this->escape_string($v). "' ";
                        break;
                    case 'integer':
                        $cons[] = "{$k} = {$v}";
                        break;
                    case 'double':
                        $cons[] = "{$k} = {$v}";
                        break;
                    case 'boolean':
                        $_v = $v ? '1 ' : '0 ';
                        $cons[] = "{$k} = ". $_v ;
                        break;
                    case 'NULL':
                        $cons[] = "{$k} = NULL ";
                        break;
                    case 'array':
                        switch(gettype($v[1])){
                            case 'string':
                                $cons[] = $k . " {$v[0]} '" .$this->escape_string($v[1]). "'";
                                break;
                            case 'integer':
                                $cons[] = "{$k} {$v[0]} {$v[1]}";
                                break;
                            case 'double':
                                $cons[] = "{$k} {$v[0]} {$v[1]}";
                                break;
                            case 'boolean':
                                $_v = $v[1] ? '1' : '0';
                                $cons[] = "{$k} {$v[0]} ". $_v ;
                                break;
                            case 'array':
                                $_v1 = [];
                                foreach($v[1] as $ik=>$iv){
                                    $ivtype = gettype($iv);
                                    if($ivtype == 'string'){
                                        $_v1[] = "'" . $this->escape_string($iv) . "'";
                                    }elseif($ivtype == 'integer' || $ivtype == 'double'){
                                        $_v1[] = $iv;
                                    }
                                }
                                if(!empty($_v1)){
                                    if($v[0] == 'IN' || $v[0] == 'in'|| $v[0] == 'not in'|| $v[0] == 'NOT IN'){
                                        $cons[] = "{$k} {$v[0]} (". implode(',', $_v1) . ')';
                                    }else{
                                        $cons[] = "{$k} {$v[0]} ". implode(' AND ', $_v1) . '';
                                    }
                                }
                                break;
                            case 'NULL':
                                $cons[] = "{$k} {$v[0]} NULL";
                                break;
                            default:
                                //errtype
                                $cons[] = "{$k} = 'IMPORTANT ERROR TYPE'";
                                break;
                        }
                        break;
                    default:
                        //errtype
                        $cons[] = "{$k} = 'IMPORTANT ERROR TYPE'";
                        break;
                }
            }
        }else{
            $cons[] = $condition;
        }


        return $cons;
    }

    protected function tableNameAssociation(string $table): string
    {
        if(!str_contains($table, '.')){
            $table = $this->schema . '.' . $table;
        }
        return (string)str_replace('.', '"."', $table);
    }
}