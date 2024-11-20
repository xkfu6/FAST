<?php

namespace app\common\model\Business;

use think\Model;

class Collevtion extends Model
{
    // 章节表
    protected $name = "business_collection";

    //指定自动插入时间戳
    protected $autoWriteTimestamp = true;

    // 插入时间自动写入的字段名
    protected $createTime = "createtime";

    //更新时候的写入字段名
    protected $updateTime = false;

    // 忽略数据表不存在的字段
    protected $field = true;
    // 软删除的字段
    protected $deleteTime = false;
}
