<?php

namespace app\common\model\product;

use think\Model;

/**
 * 商品属性关系模型
 */
class Relation extends Model
{
    // 表名
    protected $name = 'product_relation';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;

    // 追加属性



    // 分类关联查询
    public function prop()
    {
        return $this->belongsTo('app\common\model\Product\Prop', 'propid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    // 商品关联查询
    public function product()
    {
        return $this->belongsTo('app\common\model\Product\Product', 'proid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
