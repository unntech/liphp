<?php
declare (strict_types = 1);

namespace LiPhp\Models;

use LiPhp\Models\DbResult;

class MongoDBResult extends DbResult
{
    public function toObjArray()
    {
        return $this->result->toArray();
    }

    public function toArray(): array
    {
        $result = [];
        foreach($this->result as $rec){
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
}