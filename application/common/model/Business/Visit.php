<?php

namespace app\common\model\Business;

use think\Model;

class Visit extends Model
{

    //模型对应的是哪张表
    protected $name = "business_visit";

    //指定一个自动设置的时间字段
    //开启自动写入
    protected $autoWriteTimestamp = true; 

    //设置字段的名字
    protected $createTime = "createtime"; //插入的时候设置的字段名

    //禁止 写入的时间字段
    protected $updateTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;

    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin', 'adminid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
