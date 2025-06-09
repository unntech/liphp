<?php
declare (strict_types = 1);

namespace LiPhp;

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use LiPhp\Models\MongoDBResult;

class mongodb extends Db
{
	protected string $dbname;

	/**
     * 构造方法
     * @access public
     */
    public function __construct($cfg)
    {
		$this->dbname  = $cfg['dbname'];
        $this->connid = new Manager($cfg['uri']);
    }
	
	//插入数据
	public function insert(array|object $data)
    {
        $table = $this->options['table'];
		$bulk = new BulkWrite;
		$bulk->insert($data);
        return $this->connid->executeBulkWrite($this->dbname . '.' . $table, $bulk);
	}
	
	//插入多条数据
	public function inserts(array $datas)
    {
        $table = $this->options['table'];
		$bulk = new BulkWrite;
		foreach($datas as $v){
			$bulk->insert($v);
		}
        return $this->connid->executeBulkWrite($this->dbname . '.' . $table, $bulk);
	}

    /**
     * $deleteOptions = ['limit' => false]
     * 默认false为删除所有匹配，true则只删除一条
     * @return \MongoDB\Driver\WriteResult
     */
	public function delete()
    {
        $table = $this->options['table'];
		$bulk = new BulkWrite;
        $filter = $this->options['condition'] ?? [];
        $deleteOptions = $this->options['param']['options'] ?? null;
		$bulk->delete($filter, $deleteOptions);
        return $this->connid->executeBulkWrite($this->dbname . '.' . $table, $bulk);
	}

    /**
     * $updateOptions = ['multi' => false, 'upsert' => false];
     * multi 为true 则全部更新，默认false则只更新第一条
     * @param $data
     * @return \MongoDB\Driver\WriteResult
     */
	public function update($data)
    {
        $table = $this->options['table'];
		$bulk = new BulkWrite;
        $filter = $this->options['condition'] ?? [];
        $updateOptions = $this->options['param']['options'] ?? null;
		$bulk->update($filter, $data, $updateOptions);
        return $this->connid->executeBulkWrite($this->dbname . '.' . $table, $bulk);
	}

    /**
     * $filter = ['a'=>['$lt'=>9]];
     * $options = ['projection' => ['_id' => 0],'sort'=>['a'=> -1], 'limit'=>5, 'skip'=>0];
     * @return MongoDBResult
     * @throws \MongoDB\Driver\Exception\Exception
     */
	public function select()
    {
        $table = $this->options['table'];
        $filter = $this->options['condition'] ?? [];
        $param = $this->options['param'] ?? [];
        $options = $param['options'] ?? null;
        if(!empty($param['LIMIT'])){
            if(is_array($param['LIMIT'])){
                $_param = [
                    'skip' => (int)$param['LIMIT'][0],
                    'limit' => (int)$param['LIMIT'][1],
                ];
            }else{
                $_param = [
                    'limit' => (int)$param['LIMIT'],
                ];
            }
            $options = $options ? array_merge($options, $_param) : $_param;
        }
        if(!empty($param['ORDER']) && is_array($param['ORDER'])){
            $options['sort'] = $param['ORDER'];
        }

		$query = new Query($filter, $options);
		$cursor = $this->connid->executeQuery($this->dbname . '.' . $table, $query);

		return new MongoDBResult([
            'result' => $cursor,
        ]);
	}
	
	public function ISODate(int|float|null $d = null): UTCDateTime
    {
		if(is_null($d)){
			$d = microtime(true) * 1000;
		}else{
			$d = $d * 1000;
		}
        return new UTCDateTime ($d);
	}

    public function ObjectId(?string $id = null): ObjectId
    {
        return new ObjectId ($id);
    }

    public function where(array|string $condition): static
    {
        if(is_array($condition)){
            if(empty($this->options['condition']) || !is_array($this->options['condition'])){
                $this->options['condition'] = $condition;
            }else{
                $this->options['condition'] = array_merge($this->options['condition'], $condition);
            }
        }
        return $this;
    }

    public function options(?array $options): static
    {
        $this->options['param']['options'] = $options;
        return $this;
    }
}