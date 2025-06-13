<?php
declare (strict_types = 1);

namespace LiPhp;

use LiPhp\Models\SqlSrvResult;
use LiPhp\Models\DbBuilder;

class sqlsrv extends DbBuilder
{
    protected int $insertid = 0;
    protected int $affected_rows = 0;
    /**
     * 构造方法
     * @access public
     */
    public function __construct($cfg)
    {
        if($cfg['hostport']!=0 && $cfg['hostport']!=1433){
            $cfg['hostname'] .= ',' .$cfg['hostport'];
        }
        $TrustServerCertificate = $cfg['TrustServerCertificate'] ?? false;
        return $this->connect($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['dbname'], $cfg['charset'], $TrustServerCertificate);
    }

    function connect( $dbhost, $dbuser, $dbpw, $dbname, $dbcharset, $TrustServerCertificate = false ) {
        if(empty($dbcharset)){
            $dbcharset = 'UTF-8';
        }
        $connectionOptions = array(
            'CharacterSet' => $dbcharset,
            "Database" => $dbname,
            "Uid" => $dbuser,
            "PWD" => $dbpw,
            'TrustServerCertificate' => $TrustServerCertificate
        );
        //Establishes the connection
        if ( !$this->connid = sqlsrv_connect( $dbhost, $connectionOptions ) ) {
            print_r($this->errors());
            $this->halt( 'Can not connect to MsSQL server' );
        }
        return $this->connid;
    }

    protected function _query(string $sql )
    {
        $sql = trim( $sql );
        $this->sql = $sql;
        try{
            $this->insertid = 0;
            if ( preg_match( "/^insert into/i", $sql ) ) {
                $sql = "{$sql}; SELECT @@identity as insertid;";
                $query = sqlsrv_query( $this->connid, $sql );
                if($query){
                    sqlsrv_next_result( $query );
                    $insid = $this->fetch_row( $query );
                    $this->insertid = intval( $insid[ 0 ] );
                }
            } else {
                $query = sqlsrv_query( $this->connid, $sql );
            }
        }catch (\Throwable $e){
            $this->exception($e, $sql);
        }

        $_err = $this->errors();
        $this->errorCode = $_err[0][1] ?? 0;
        $this->errorMessage = $_err[0][0] ?? '';
        $this->query_finished = true;
        $n = sqlsrv_rows_affected( $query );
        $this->affected_rows = $n === false ? -1 : $n;
        return $query;
    }

    public function query(string $sql): SqlSrvResult
    {
        $query = $this->_query($sql);
        return $this->result($query);
    }

    public function result($query): SqlSrvResult
    {
        return SqlSrvResult::instance([
            'result'        => $query,
            'sql'           => $this->sql,
            'insertId'      => $this->insertid,
            'affected_rows' => $this->affected_rows(),
            'errorCode'     => $this->errorCode,
            'errorMessage'  => $this->errorMessage,
        ]);
    }

    //事务操作
    public function startTrans()
    {
        return $this->_query('BEGIN TRANSACTION');
    }

    public function commit()
    {
        return $this->_query('COMMIT TRANSACTION');
    }

    public function rollback()
    {
        return $this->_query('ROLLBACK TRANSACTION');
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

        $table = str_replace('.', '].[', $table);
        if(!is_array($fields) || empty($fields)){
            return false;
        }
        $this->sql = '';
        $ufields = $this->_fields_strip($fields);
        if(empty($ufields)){
            return false;
        }
        $sql = "UPDATE [{$table}] SET " . implode(', ', $ufields);
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
        if($this->options['fetchSql']){ return $sql; }
        $res =  $this->_query($sql);
        return SqlSrvResult::instance([
            'result'        => $res,
            'sql'           => $this->sql,
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

        $table = str_replace('.', '].[', $table);
        $this->sql = '';
        $sql = "DELETE FROM [{$table}] ";
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
        if($this->options['fetchSql']){ return $sql; }
        $res = $this->_query($sql);
        return SqlSrvResult::instance([
            'result'        => $res,
            'sql'           => $this->sql,
            'affected_rows' => $this->affected_rows(),
            'errorCode'     => $this->errorCode,
            'errorMessage'  => $this->errorMessage,
        ]);
    }

    /**
     * 插入数据
     * @param array $data
     * @param bool $returnResult
     * @return false|int|string|SqlSrvResult
     */
    public function insert(array $data = [], bool $returnResult = false)
    {
        $table = $this->options['table'];
        if(empty($table) || $this->query_finished !== false){ //未设置表名
            return false;
        }

        $table = str_replace('.', '`.`', $table);
        $this->sql = '';
        if(empty($data)){
            return false;
        }
        $d = $this->_fields_split($data);
        $sql = "INSERT INTO [{$table}] (" . implode(',', $d[0]) . ") VALUES (" . implode(',', $d[1]) .") ";
        if($this->options['fetchSql']){ return $sql; }

        $res = $this->_query($sql) ;
        if($returnResult){
            return SqlSrvResult::instance([
                'result'        => $res,
                'sql'           => $this->sql,
                'insertId'      => $this->insertid,
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
     * @param array $data 数据集 二级数组
     * @return false|SqlSrvResult|string
     */
    public function insertAll(array $data = [])
    {
        $table = $this->options['table'];
        if(empty($table) || $this->query_finished !== false){ //未设置表名
            return false;
        }

        $table = str_replace('.', '`.`', $table);
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

        $sql = "INSERT INTO [{$table}] (" . implode(',', $d[0][0]) . ") VALUES ";
        $first = true;
        foreach($d as $di){
            if(!$first){ $sql .= ', ';}
            $sql .= "(" . implode(',', $di[1]) . ") ";
            $first = false;
        }
        if($this->options['fetchSql']){ return $sql; }

        $res = $this->_query($sql);
        return SqlSrvResult::instance([
            'result'        => $res,
            'sql'           => $this->sql,
            'insertId'      => $this->insertid,
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

        $table = str_replace('.', '].[', $table);
        $this->sql = '';
        if(!empty($param['LIMIT']) && !is_array($param['LIMIT'])){
            $_top = 'TOP '.intval($param['LIMIT']);
        }else{
            $_top = '';
        }
        if(empty($fields)){
            $sql = "SELECT {$_top} ".(empty($alias) ? '' : "{$alias}.")."* FROM [{$table}] ";
        }else{
            $ct = gettype($fields);
            if($ct == 'string'){
                $fields = preg_replace('/[^A-Za-z0-9_,\-\. `()\*\[\]]/', '', $fields);
                $sql = "SELECT {$_top} {$fields} FROM [{$table}] ";
            }elseif($ct == 'array'){
                $sql = "SELECT {$_top} ". implode(',', $fields) ." FROM [{$table}] ";
            }else{
                $sql = "SELECT {$_top} noFields FROM [{$table}] ";
            }
        }
        if(!empty($alias)){
            $sql .= "AS {$alias} ";
        }

        if(!empty($param['JOIN'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. `=\[\]]/', '', $param['JOIN']);
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
            $str = preg_replace('/[^A-Za-z0-9_,\. `]/', '', $param['GROUPBY']);
            $sql .= " GROUP BY " .$str;
        }
        if(!empty($param['ORDER'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. `]/', '', $param['ORDER']);
            $sql .= " ORDER BY " .$str;
        }
        if(!empty($param['LIMIT'])){
            if(is_array($param['LIMIT'])){
                $sql .= " OFFSET ".intval($param['LIMIT'][0]).' ROWS FETCH NEXT '.intval($param['LIMIT'][1]).' ROWS ONLY';
            }else{
                //$sql .= " OFFSET 0 ROWS FETCH NEXT ".intval($param['LIMIT']).' ROWS ONLY';
            }
        }
        if($this->options['fetchSql']){ return $sql; }

        $res = $this->_query($sql);
        return SqlSrvResult::instance([
            'result'        => $res,
            'sql'           => $this->sql,
            'errorCode'     => $this->errorCode,
            'errorMessage'  => $this->errorMessage,
        ]);
    }

    /*
     * 查询一条数据
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

        $table = str_replace('.', '].[', $table);
        $this->sql = '';
        if(empty($fields)){
            $sql = "SELECT TOP 1 ".(empty($alias) ? '' : "{$alias}.")."* FROM [{$table}] ";
        }else{
            $ct = gettype($fields);
            if($ct == 'string'){
                $fields = preg_replace('/[^A-Za-z0-9_,\-\. `()\*\[\]]/', '', $fields);
                $sql = "SELECT TOP 1  {$fields} FROM [{$table}] ";
            }elseif($ct == 'array'){
                $sql = "SELECT TOP 1 ". implode(',', $fields) ." FROM [{$table}] ";
            }else{
                $sql = "SELECT TOP 1 Fields FROM [{$table}] ";
            }
        }

        if(!empty($alias)){
            $sql .= "AS {$alias} ";
        }

        if(!empty($param['JOIN'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. `=\[\]]/', '', $param['JOIN']);
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
            $str = preg_replace('/[^A-Za-z0-9_,\. `]/', '', $param['GROUPBY']);
            $sql .= " GROUP BY " .$str;
        }
        if(!empty($param['ORDER'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. `]/', '', $param['ORDER']);
            $sql .= " ORDER BY " .$str;
        }

        if($this->options['fetchSql']){ return $sql; }

        $res = $this->_query($sql) ;
        return $this->fetch_assoc($res);
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

        $table = str_replace('.', '].[', $table);
        $this->sql = '';
        $ct = gettype($fields);
        if(empty($fields) || $ct != 'string'){
            return false;
        }
        $fields = preg_replace('/[^A-Za-z0-9_,\-\. `()\*\[\]]/', '', $fields);
        $sql = "SELECT TOP 1 {$fields} FROM [{$table}] ";

        if(!empty($alias)){
            $sql .= "AS {$alias} ";
        }

        if(!empty($param['JOIN'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. `=\[\]]/', '', $param['JOIN']);
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
            $str = preg_replace('/[^A-Za-z0-9_,\. `]/', '', $param['GROUPBY']);
            $sql .= " GROUP BY " .$str;
        }
        if(!empty($param['ORDER'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. `]/', '', $param['ORDER']);
            $sql .= " ORDER BY " .$str;
        }

        if($this->options['fetchSql']){ return $sql; }

        $res = $this->_query($sql);
        $r = $this->fetch_row($res);
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

        $table = str_replace('.', '].[', $table);
        $this->sql = '';
        $sql = "SELECT COUNT(*) AS amount FROM [{$table}]";
        if(!empty($alias)){
            $sql .= "AS {$alias} ";
        }

        if(!empty($param['JOIN'])){
            $str = preg_replace('/[^A-Za-z0-9_,\. `=\[\]]/', '', $param['JOIN']);
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
        $r = $this->fetch_assoc($res);
        return $r ? $r['amount'] : 0;
    }

    protected function fetch_assoc($res)
    {
        return sqlsrv_fetch_array( $res, SQLSRV_FETCH_ASSOC );
    }

    protected function fetch_row($res)
    {
        return sqlsrv_fetch_array( $res, SQLSRV_FETCH_NUMERIC );
    }

    public function close()
    {
        return sqlsrv_close( $this->connid );
    }

    public function insert_id()
    {
        return $this->insertid;
    }

    public function affected_rows()
    {
        return $this->affected_rows;
    }

    public function server_info()
    {
        return sqlsrv_server_info( $this->connid );
    }

    public function client_info()
    {
        return sqlsrv_client_info( $this->connid );
    }

    public function errors()
    {
        return sqlsrv_errors();
    }

    public function errno()
    {
        $e = $this->errors();
        return $e[0][1] ?? 0;
    }

    protected function halt( $message = '', $sql = '' ) {
        if(defined('DT_DEBUG') && DT_DEBUG){
            echo "<div>".$sql."</div><div>".LiComm::dv($this->errors(), false)."</div><div>".$message."</div>\n";
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

    public function removeEscape($str)
    {
        $str = str_replace(array('\'','"','\\'),"",$str);
        return $str;
    }

    public function escape_string($str)
    {
        return $str;
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
                    $fk[] = "[{$k}]";
                    $fv[] = "'" .$this->escape_string($v). "'";
                    break;
                case 'integer':
                    $fk[] = "[{$k}]";
                    $fv[] = "'{$v}'";
                    break;
                case 'double':
                    $fk[] = "[{$k}]";
                    $fv[] = "'{$v}'";
                    break;
                case 'boolean':
                    $_v = $v ? '1 ' : '0 ';
                    $fk[] = "[{$k}]";
                    $fv[] = "'{$_v}'";
                    break;
                case 'NULL':
                    $fk[] = "[{$k}]";
                    $fv[] = 'NULL';
                    break;
                default:
                    $fk[] = "[{$k}]";
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
                                if($v[0] == 'CONTAINS' || $v[0] == 'contains'){
                                    $cons[] = "CONTAINS({$k}, '".$this->escape_string($v[1])."')";
                                }else{
                                    $cons[] = $k . " {$v[0]} '" .$this->escape_string($v[1]). "'";
                                }
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
}
