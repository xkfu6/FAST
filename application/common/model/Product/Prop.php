<?php

namespace app\common\model\product;

use think\Model;
use think\Config;

/**
 * 商品属性模型
 */
class Prop extends Model
{
    // 表名
    protected $name = 'product_prop';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;
}
