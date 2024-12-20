<?php

namespace app\common\model\Product;

use think\Model;


class Type extends Model
{
    // 表名
    protected $name = 'product_type';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;

    // 追加属性
    protected $append = [

    ];

}
