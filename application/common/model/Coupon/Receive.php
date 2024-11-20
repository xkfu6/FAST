<?php

namespace app\common\model\Coupon;

use think\Model;

class Receive extends Model
{
    // 表名
    protected $name = 'coupon_receive';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'createtime_text',
    ];

    //时间戳
    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = $data['createtime'];
        
        if(empty($createtime))
        {
            return '';
        }

        return date("Y-m-d", $createtime);
    }

     // 关联用户
     public function business()
     {
         return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
     }

     public function coupon()
     {
         return $this->belongsTo('app\common\model\Coupon\Coupon', 'cid', 'id', [], 'LEFT')->setEagerlyType(0);
     }
}
