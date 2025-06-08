<?php
declare (strict_types = 1);

namespace LiPhp;

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;
use MongoDB\BSON\UTCDateTime;
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
     * @return array
     * @throws \MongoDB\Driver\Exception\Exception
     */
	public function query()
    {
        $table = $this->options['table'];
        $filter = $this->options['condition'] ?? [];
        $options = $this->options['param']['options'] ?? null;
		$query = new Query($filter, $options);
		$cursor = $this->connid->executeQuery($this->dbname . '.' . $table, $query);
		
		return $cursor->toArray();
	}
	
	//把query出来的数据对象转换成数组的扩展方法，方便查看使用
	public function cursorObjToArray($cursor){
		$result = [];
		foreach($cursor as $rec){
			$r = [];
			foreach($rec as $k => $v){
				if(gettype($v) == 'object'){
					switch(get_class($v)){
						case 'MongoDB\BSON\ObjectId':
							$_v = $v->__toString();
							$r['_time'] = $v->getTimestamp();
							break;
						case 'MongoDB\BSON\UTCDateTime':
							$_v = $v->toDateTime()->setTimezone(new \DateTimeZone('Asia/Shanghai'));
							break;
						default:
							$_v = $v;
					}
				}else{
					$_v = $v;
				}
				$r[$k] = $_v;
			}
			$result[] = $r;
		}
		return($result);
	}
	
	public function ISODate(int|float|null $d = null){
		if(is_null($d)){
			$d = microtime(true) * 1000;
		}else{
			$d = $d * 1000;
		}
        return new UTCDateTime ($d);
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