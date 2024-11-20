<?php

namespace app\common\model\Business;

use think\Model;

class Collection extends Model
{
    protected $name = 'business_collection';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;
    
    // 追加属性
    protected $append = [
        'createtime_text'
    ];

    // 定义日期格式
    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = isset($data['createtime']) ? $data['createtime'] : 0;

        return date('Y-m-d H:i', $createtime);
    }

    public function product()
    {
        return $this->belongsTo('app\common\model\Product\Product','collectid','id',[],'LEFT')->setEagerlyType(0);
    }

    public function subject()
    {
        return $this->belongsTo('app\common\model\Subject\Subject','collectid','id',[],'LEFT')->setEagerlyType(0);
    }
}
