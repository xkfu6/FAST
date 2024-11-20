<?php

namespace app\common\model\Business;

use think\Model;

class Source extends Model
{

    //模型对应的是哪张表
    protected $name = "business_source";

    //指定一个自动设置的时间字段
    //开启自动写入
    protected $autoWriteTimestamp = false;

    //禁止 写入的时间字段
    protected $updateTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;
}
