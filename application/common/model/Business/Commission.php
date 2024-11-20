<?php

namespace app\common\model\Business;

use think\Model;

// 软删除的模型
use traits\model\SoftDelete;

class Commission extends Model
{
    //继承软删除
    use SoftDelete;

    //模型对应的是哪张表
    protected $name = "business_commission";

    //指定一个自动设置的时间字段
    //开启自动写入
    protected $autoWriteTimestamp = true;

    //设置字段的名字
    protected $createTime = "createtime"; //插入的时候设置的字段名

    //禁止 写入的时间字段
    protected $updateTime = false;

    // 软删除的字段
    protected $deleteTime = 'deletetime';

    //自动过滤掉不存在的字段
    protected $field = true;


    // public function subject()
    // {
    //     return $this->belongsTo('app\common\model\Subject\Order', 'orderid', 'id', [], 'LEFT')->setEagerlyType(0);
    // }

    public function product()
    {
        return $this->belongsTo('app\common\model\Order\Order', 'orderid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    // 课程购买订单
    public function order()
    {
        return $this->belongsTo('app\common\model\Subject\Order', 'orderid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function parentid()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'parentid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    // 商城购买订单
    public function sporder(){
        return $this->belongsTo('app\common\model\Order\Order', 'orderid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function orderProduct(){
        return $this->belongsTo('app\common\model\Order\Product', 'orderid', 'orderid', [], 'LEFT')->setEagerlyType(0);
    }
}
